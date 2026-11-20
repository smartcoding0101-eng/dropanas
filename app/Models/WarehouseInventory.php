<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseInventory extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'stock_physical',
        'stock_reserved',
        'stock_available',
        'visible_public',
        'visible_sellers',
    ];

    protected $casts = [
        'visible_public'  => 'boolean',
        'visible_sellers' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sellerReserves()
    {
        return $this->hasMany(WarehouseSellerReserve::class, 'product_id', 'product_id')
            ->where('warehouse_id', $this->warehouse_id)
            ->active();
    }
}

