<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProductBatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'productBatches';
    protected static ?string $title = 'Batch Produksi';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('stok_toko')
                    ->label('Stok Awal')
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\DatePicker::make('tanggal_kedaluwarsa')
                    ->label('Tanggal Kedaluwarsa')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
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
                //
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
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->stok_toko <= 0),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->stok_toko <= 0),
            ]);
    }
}
