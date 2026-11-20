<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'provider_id',
        'name',
        'code',
        'department',
        'city',
        'address',
        'latitude',
        'longitude',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function sellerReserves(): HasMany
    {
        return $this->hasMany(WarehouseSellerReserve::class);
    }

    public function stockTransfersOut(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function stockTransfersIn(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /** Stock total disponible del almacén */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->inventories()->sum('stock_available');
    }

    /** Número de productos únicos con stock > 0 en este almacén */
    public function getActiveProductsCountAttribute(): int
    {
        return $this->inventories()->where('stock_available', '>', 0)->count();
    }
}

