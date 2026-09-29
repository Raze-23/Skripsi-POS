<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource\Actions\ExportSuratTugasAction;
use App\Filament\Resources\PartnerResource;
use App\Models\PaymentAccount;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListPartners extends ListRecords
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportSuratTugasAction::make(),

            Actions\Action::make('rekening_pembayaran')
                ->label('Rekening Pembayaran')
                ->icon('heroicon-o-building-library')
                ->modalHeading('Rekening Tujuan Pembayaran Mitra')
                ->modalDescription('Rekening aktif dapat dipilih Mitra ketika mengunggah bukti transfer. Nonaktifkan rekening yang tidak digunakan lagi.')
                ->modalWidth('3xl')
                ->fillForm(fn (): array => [
                    'rekening' => PaymentAccount::orderBy('id')->get()
                        ->map(fn (PaymentAccount $account): array => [
                            'id' => $account->id,
                            'bank' => $account->bank,
                            'account_number' => $account->account_number,
                            'account_name' => $account->account_name,
                            'is_active' => $account->is_active,
                        ])->all(),
                ])
                ->form([
                    Forms\Components\Repeater::make('rekening')
                        ->label('Daftar Rekening')
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\TextInput::make('bank')->label('Nama Bank')->required()->maxLength(100),
                            Forms\Components\TextInput::make('account_number')
                                ->label('Nomor Rekening')
                                ->required()
                                ->maxLength(20)
                                ->helperText('Isi nomor rekening yang benar; angka nol di depan tetap tersimpan.'),
                            Forms\Components\TextInput::make('account_name')->label('Atas Nama')->required()->maxLength(255),
                            Forms\Components\Toggle::make('is_active')->label('Aktif untuk Mitra')->default(true),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Tambah Rekening')
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data): void {
                        foreach ($data['rekening'] ?? [] as $row) {
                            $account = filled($row['id'] ?? null)
                                ? PaymentAccount::findOrFail($row['id'])
                                : new PaymentAccount();
                            $account->fill([
                                'bank' => $row['bank'],
                                'account_number' => $row['account_number'],
                                'account_name' => $row['account_name'],
                                'is_active' => (bool) ($row['is_active'] ?? false),
                            ])->save();
                        }
                    });
                    Notification::make()->success()->title('Rekening Pembayaran Tersimpan')->send();
                }),

            Actions\CreateAction::make()
            ->label('Tambah Mitra')
            ->icon('heroicon-o-plus-circle')
        ];
    }
}
