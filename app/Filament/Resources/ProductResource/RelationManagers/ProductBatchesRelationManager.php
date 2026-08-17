<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProductBatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'productBatches';
    protected static ?string $title = 'Batch Produk';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Batch')
                ->description('Catat jumlah stok dan tanggal kedaluwarsa untuk batch produksi ini.')
                ->icon('heroicon-o-cube')
                ->schema([
                    Forms\Components\TextInput::make('stok_toko')
                        ->label('Jumlah Stok')
                        ->numeric()
                        ->default(0)
                        ->rules(['required', 'min:1'])
                        ->markAsRequired(true)
                        ->autofocus()
                        ->prefixIcon('heroicon-o-archive-box')
                        ->suffix('Pcs')
                        ->disabled(function (?Model $record) {
                            if (! $record) {
                                return false; 
                            }
                            
                            $hasDisposals = $record->productDisposals()->exists();
                            $hasConsignments = $record->consignmentStocks()->exists();
                            
                            return $hasDisposals || $hasConsignments;
                        })
                        ->helperText(function (?Model $record) {
                            if (! $record) {
                                return 'Jumlah unit yang akan masuk ke stok toko untuk batch ini.';
                            }
                            
                            $hasDisposals = $record->productDisposals()->exists();
                            $hasConsignments = $record->consignmentStocks()->exists();
                            
                            if ($hasDisposals || $hasConsignments) {
                                return '🔒 Terkunci';
                            }
                            
                            return 'Jumlah unit yang akan masuk ke stok toko untuk batch ini.';
                        })
                        ->validationMessages([
                            'required' => 'Jumlah stok wajib diisi.',
                            'min' => 'Jumlah stok minimal 1.',
                            'min.numeric' => 'Jumlah stok minimal 1.',
                        ]),
                    Forms\Components\DatePicker::make('tanggal_kedaluwarsa')
                        ->label('Tanggal Kedaluwarsa')
                        ->rules(['required'])
                        ->markAsRequired(true)
                        ->native(false)
                        ->closeOnDateSelection()
                        ->displayFormat('d/m/Y')
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->minDate(today())
                        ->helperText('Pilih tanggal kedaluwarsa produk pada batch ini.')
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Daftar Batch Produk') 
            ->modifyQueryUsing(fn (Builder $query) => $query->where('stok_toko', '>', 0))
            ->recordTitleAttribute('batch_code')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('batch_code')
                    ->label('QR & Kode Batch')
                    ->formatStateUsing(function (string $state) {
                        $qr = QrCode::size(50)->generate($state);
                        return new HtmlString('<div style="display:flex; flex-direction:column; align-items:center; gap:5px; padding-top: 5px;">' . $qr . '<span style="font-size: 11px; font-family: monospace;">' . $state . '</span></div>');
                    })
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stok_toko')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(int $state): string => $state < 10 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_kedaluwarsa')
                    ->label('Kedaluwarsa')
                    ->date('d M Y')
                    ->badge()
                    ->color(function ($state): string {
                        if (!$state) return 'gray';
                        $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse($state)->startOfDay(), false);
                        return match (true) {
                            $daysLeft < 7  => 'danger',
                            $daysLeft < 30 => 'warning',
                            $daysLeft < 60 => 'info',
                            default        => 'success',
                        };
                    })
                    ->icon(function ($state): string {
                        if (!$state) return 'heroicon-o-minus';
                        $daysLeft = now()->startOfDay()->diffInDays(Carbon::parse($state)->startOfDay(), false);
                        return match (true) {
                            $daysLeft < 7  => 'heroicon-o-x-circle',
                            $daysLeft < 30 => 'heroicon-o-exclamation-triangle',
                            $daysLeft < 60 => 'heroicon-o-clock',
                            default        => 'heroicon-o-shield-check',
                        };
                    })
                    ->sortable(),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Batch Baru')
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Batch Berhasil Dibuat')
                            ->body('Data produksi batch baru telah masuk ke dalam sistem dan siap didistribusikan.')
                            ->icon('heroicon-o-check-badge')
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->stok_toko <= 0)
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Berhasil Diperbarui')
                            ->body('Data berhasil diperbarui dan disimpan!')
                            ->icon('heroicon-o-document-check')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->stok_toko <= 0)
                    ->before(function ($record, Tables\Actions\DeleteAction $action) {
                        if (
                            $record->productDisposals()->exists() ||
                            $record->consignmentStocks()->exists() ||
                            $record->consignmentReturns()->exists() ||
                            $record->transactionDetails()->exists()
                        ) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal Menghapus')
                                ->body('Batch ini tidak dapat dihapus karena sudah memiliki riwayat transaksi, konsinyasi, atau pembuangan.')
                                ->send();
                            
                            $action->halt();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->danger()
                            ->title('Batch Berhasil Dihapus!')
                            ->body('Data batch produk telah dihapus secara permanen dari sistem.')
                            ->icon('heroicon-o-trash')
                    ),
            ]);
    }
}