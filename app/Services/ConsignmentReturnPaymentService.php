<?php

namespace App\Services;

use App\Models\ConsignmentReturn;
use App\Models\ConsignmentStock;
use App\Models\PaymentAccount;
use App\Models\ProductDisposal;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ConsignmentReturnPaymentService
{
    public function submit(ConsignmentReturn $return, User $mitra, array $data): void
    {
        if ($mitra->role !== 'mitra' || $mitra->partner_id !== $return->partner_id) {
            abort(403);
        }

        $sold = (int) ($data['terjual'] ?? 0);
        $good = (int) ($data['qty_layak'] ?? 0);
        $damaged = (int) ($data['qty_rusak'] ?? 0);
        $method = $sold > 0 ? ($data['metode_pembayaran'] ?? null) : null;
        if (min($sold, $good, $damaged) < 0 || ($sold > 0 && ! in_array($method, ['tunai_sales', 'transfer_bank'], true))) {
            throw ValidationException::withMessages(['metode_pembayaran' => 'Pilih metode pembayaran yang valid.']);
        }
        if ($method === 'transfer_bank' && (blank($data['bukti_pembayaran'] ?? null) || blank($data['nama_pengirim'] ?? null) || blank($data['bank_pengirim'] ?? null) || blank($data['dibayar_pada'] ?? null))) {
            throw ValidationException::withMessages(['bukti_pembayaran' => 'Lengkapi pengirim, bank, waktu, dan bukti transfer.']);
        }
        if ($method === 'transfer_bank' && (! str_starts_with($data['bukti_pembayaran'], "bukti-pembayaran-retur/{$return->id}/") || ! Storage::disk('local')->exists($data['bukti_pembayaran']))) {
            throw ValidationException::withMessages(['bukti_pembayaran' => 'Bukti transfer tidak ditemukan. Unggah ulang foto pembayaran.']);
        }
        $account = $method === 'transfer_bank'
            ? PaymentAccount::where('is_active', true)->find($data['payment_account_id'] ?? null)
            : null;
        if ($method === 'transfer_bank' && ! $account) {
            throw ValidationException::withMessages(['payment_account_id' => 'Pilih rekening tujuan yang aktif.']);
        }

        DB::transaction(function () use ($return, $data, $sold, $good, $damaged, $method, $account) {
            $return = ConsignmentReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($return->status !== 'menunggu_konfirmasi') {
                throw ValidationException::withMessages(['terjual' => 'Rincian retur ini sudah diajukan. Muat ulang halaman.']);
            }
            $stock = ConsignmentStock::query()->where('partner_id', $return->partner_id)
                ->where('product_batch_id', $return->product_batch_id)->lockForUpdate()->first();
            if (! $stock || $sold + $good + $damaged !== (int) $stock->stok_titipan) {
                throw ValidationException::withMessages(['terjual' => 'Jumlah rincian harus sama dengan stok titipan saat ini.']);
            }

            $return->update([
                'terjual' => $sold, 'qty_layak' => $good, 'qty_rusak' => $damaged,
                'harga_satuan' => $return->resolvedUnitPrice($stock),
                'diskon_persen' => $stock->diskon_persen,
                'omzet_terbentuk' => $return->calculateRevenue($sold, $stock),
                'metode_pembayaran' => $method,
                'bukti_pembayaran' => $method === 'transfer_bank' ? $data['bukti_pembayaran'] : null,
                'nama_pengirim' => $method === 'transfer_bank' ? $data['nama_pengirim'] : null,
                'bank_pengirim' => $method === 'transfer_bank' ? $data['bank_pengirim'] : null,
                'referensi_transfer' => $method === 'transfer_bank' ? ($data['referensi_transfer'] ?? null) : null,
                'bank_tujuan' => $account?->bank,
                'rekening_tujuan' => $account?->account_number,
                'pemilik_rekening_tujuan' => $account?->account_name,
                'dibayar_pada' => $method === 'transfer_bank' ? $data['dibayar_pada'] : null,
                'diajukan_pada' => now(),
                'catatan_penolakan' => null,
                'status' => $sold > 0 ? 'menunggu_validasi' : 'selesai',
            ]);

            if ($sold === 0) {
                $this->finalizeStock($return, $stock);
            }
        });

        $this->clearMitraReminder($return);
        if ($sold > 0) {
            $return->refresh();
            foreach (User::where('role', 'admin')->where('status', 'aktif')->get() as $admin) {
                Notification::make()->warning()->title('Menunggu Validasi Pembayaran')
                    ->body('Pembayaran dari '.$return->partner->nama_apotek.' sebesar Rp '.number_format($return->omzet_terbentuk, 0, ',', '.').' menunggu pemeriksaan.')
                    ->viewData(['alert_key' => "validasi_retur_{$return->id}"])
                    ->actions([\Filament\Notifications\Actions\Action::make('lihat')
                        ->label('Buka Riwayat Penarikan')
                        ->url(\App\Filament\Resources\PartnerResource::getUrl('edit', [
                            'record' => $return->partner_id,
                            'activeRelationManager' => '1',
                        ], panel: 'admin'))])
                    ->sendToDatabase($admin);
            }
        }
    }

    public function approve(ConsignmentReturn $return, User $admin): void
    {
        if ($admin->role !== 'admin') {
            abort(403);
        }
        DB::transaction(function () use ($return, $admin) {
            $return = ConsignmentReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($return->status !== 'menunggu_validasi') {
                throw ValidationException::withMessages(['status' => 'Pembayaran sudah diproses.']);
            }
            $stock = ConsignmentStock::query()->where('partner_id', $return->partner_id)
                ->where('product_batch_id', $return->product_batch_id)->lockForUpdate()->first();
            if (! $stock || $return->terjual + $return->qty_layak + $return->qty_rusak !== $stock->stok_titipan) {
                throw ValidationException::withMessages(['status' => 'Stok titipan berubah. Periksa sebelum validasi.']);
            }
            $this->finalizeStock($return, $stock);
            $return->update(['status' => 'selesai', 'divalidasi_pada' => now(), 'divalidasi_oleh' => $admin->id]);
        });
        $this->clearAdminReminder($return);
        $this->notifyMitra($return, 'Pembayaran Diterima', 'Pembayaran dan penarikan '.$return->productBatch->product->nama.' telah selesai.');
    }

    public function reject(ConsignmentReturn $return, User $admin, string $reason): void
    {
        if ($admin->role !== 'admin') {
            abort(403);
        }
        $oldProof = DB::transaction(function () use ($return, $reason) {
            $return = ConsignmentReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($return->status !== 'menunggu_validasi') {
                throw ValidationException::withMessages(['status' => 'Pembayaran sudah diproses.']);
            }
            $proof = $return->bukti_pembayaran;
            $return->update(['status' => 'menunggu_konfirmasi', 'catatan_penolakan' => $reason,
                'bukti_pembayaran' => null, 'diajukan_pada' => null]);
            return $proof;
        });
        if ($oldProof) {
            Storage::disk('local')->delete($oldProof);
        }
        $this->clearAdminReminder($return);
        $this->notifyMitra($return, 'Pembayaran Perlu Diperbaiki', $reason);
    }

    private function finalizeStock(ConsignmentReturn $return, ConsignmentStock $stock): void
    {
        if ($return->qty_layak > 0) {
            $return->productBatch()->increment('stok_toko', $return->qty_layak);
        }
        if ($return->qty_rusak > 0) {
            ProductDisposal::create(['product_batch_id' => $return->product_batch_id,
                'jumlah' => $return->qty_rusak, 'alasan' => 'Barang Rusak',
                'sumber' => 'Apotek', 'consignment_return_id' => $return->id]);
        }
        $stock->delete();
    }

    private function clearMitraReminder(ConsignmentReturn $return): void
    {
        foreach ($return->partner->users()->where('role', 'mitra')->get() as $user) {
            $user->notifications()->where('data->viewData->alert_key', "pengingat_retur_{$return->id}_user_{$user->id}")->delete();
        }
    }

    private function notifyMitra(ConsignmentReturn $return, string $title, string $body): void
    {
        foreach ($return->partner->users()->where('role', 'mitra')->where('status', 'aktif')->get() as $user) {
            Notification::make()->title($title)->body($body)->sendToDatabase($user);
        }
    }

    private function clearAdminReminder(ConsignmentReturn $return): void
    {
        foreach (User::where('role', 'admin')->get() as $admin) {
            $admin->notifications()->where('data->viewData->alert_key', "validasi_retur_{$return->id}")->delete();
        }
    }
}
