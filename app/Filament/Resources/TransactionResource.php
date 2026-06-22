<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\Sales;
use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    protected static ?string $pluralLabel = 'Riwayat Transaksi';

    protected static ?int $navigationSort= 1;

    protected static ?string $cluster = Sales::class ;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No. Nota')
                    ->formatStateUsing(fn ($state) => 'NOTA-' . str_pad($state, 5, '0', STR_PAD_LEFT))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('kasir.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_harga')
                    ->label('Total Tagihan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kasir_id')
                    ->relationship('kasir', 'name')
                    ->label('Yang melayani transaksi'),

                Filter::make('hari_ini')
                    ->label('Transaksi Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', Carbon::today())),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Rincian')
                        ->color('info')
                        ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->color('danger')->label('Tutup')),
                        
                    Action::make('cetak')
                        ->label('Cetak Nota')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->action(function (Transaction $record, $livewire) {
                            $url = route('print.nota', $record->id);
                            $livewire->js("
                                (function() {
                                    var oldFrame = document.getElementById('frame-cetak-nota-riwayat');
                                    if (oldFrame) oldFrame.remove();
                                    var iframe = document.createElement('iframe');
                                    iframe.id = 'frame-cetak-nota-riwayat';
                                    iframe.src = '{$url}';
                                    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:1px;height:1px;border:none;visibility:hidden;';
                                    document.body.appendChild(iframe);
                                })();
                            ");
                        }),

                    Action::make('batalkan')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Transaksi?')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan transaksi ini? Stok produk akan dikembalikan otomatis ke toko.')
                        ->modalSubmitActionLabel('Ya, Batalkan')
                        ->authorize('cancel')
                        ->visible(fn (?Transaction $record) => $record !== null && $record->status !== 'Batal')
                        ->action(function (Transaction $record) {
                            DB::transaction(function () use ($record) {
                                $record->status = 'Batal';
                                $record->save();

                                foreach ($record->details as $item) {
                                    if ($item->product) {
                                        $item->product->increment('stok_toko', $item->qty);
                                    }
                                }
                            });
                            Notification::make()
                                ->title('Transaksi Dibatalkan')
                                ->body('Status diubah menjadi Batal dan stok telah dikembalikan.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                //
            ]);}

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Transaksi')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextEntry::make('id')
                            ->label('Nomor Nota')
                            ->formatStateUsing(fn ($state) => 'NOTA-' . str_pad($state, 5, '0', STR_PAD_LEFT)),
                        TextEntry::make('created_at')
                            ->label('Waktu Transaksi')
                            ->dateTime('d M Y, H:i:s'),
                        TextEntry::make('kasir.name')
                            ->label('Kasir Bertugas'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Selesai' => 'success',
                                'Batal' => 'danger',
                                default => 'warning',
                            }),
                    ])->columns(4),

                Section::make('Daftar Pembelian')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->label('')
                            ->schema([
                                TextEntry::make('product.nama')
                                    ->label('Nama Produk')
                                    ->weight('bold'),
                                TextEntry::make('qty')
                                    ->label('Kuantitas')
                                    ->suffix(' pcs'),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->money('IDR', locale: 'id')
                                    ->color('primary'),
                            ])
                            ->columns(3)
                    ]),

                Section::make('Rincian Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('diskon_persen')
                                ->label('Diskon Diberikan')
                                ->suffix('%'),
                            TextEntry::make('total_harga')
                                ->label('Total Tagihan')
                                ->money('IDR', locale: 'id')
                                ->size(TextEntry\TextEntrySize::Large)
                                ->weight('black'),
                            TextEntry::make('nominal_bayar')
                                ->label('Uang Tunai')
                                ->money('IDR', locale: 'id'),
                            TextEntry::make('nominal_kembalian')
                                ->label('Kembalian')
                                ->money('IDR', locale: 'id')
                                ->color('success')
                                ->weight('bold'),
                        ])
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
