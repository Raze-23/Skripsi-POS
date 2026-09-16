<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'user_id',
        'partner_id',
        'product_id',
        'tipe_request',
        'jumlah',
        'diskon_persen',
        'harga_satuan',
        'status',
    ];

    protected $casts = [
        'diskon_persen' => 'decimal:2',
        'harga_satuan' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductRequest $request) {
            if ($request->tipe_request !== 'restok_apotek' || $request->partner_id) {
                return;
            }

            $request->partner_id = User::query()
                ->whereKey($request->user_id)
                ->value('partner_id');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function delivery()
    {
        return $this->hasOne(ConsignmentDelivery::class);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Keputusan',
            self::STATUS_DIPROSES => 'Diproses',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DITOLAK => 'Ditolak',
        ];
    }

    public function requestSourceName(): string
    {
        if ($this->tipe_request === 'produksi_owner') {
            return 'Owner / Gudang';
        }

        return $this->partner?->nama_apotek
            ?? $this->user?->partner?->nama_apotek
            ?? 'Apotek belum terhubung';
    }
}
