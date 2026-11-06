<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'cost_price_bob',
        'suggested_price_bob',
        'min_price_bob',
        'weight_kg',
        'status',
        'is_cod_available',
        'images',
        'attributes',
    ];

    protected $casts = [
        'cost_price_bob' => 'decimal:2',
        'suggested_price_bob' => 'decimal:2',
        'min_price_bob' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'is_cod_available' => 'boolean',
        'images' => 'array',
        'attributes' => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function warehouseInventories(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    public function getTotalAvailableStockAttribute(): int
    {
        return (int) $this->warehouseInventories()->sum('stock_available');
    }
}
