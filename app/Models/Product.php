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
        'internal_code',
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

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->internal_code)) {
                $product->internal_code = static::generateNextInternalCode();
            }
        });
    }

    /**
     * Genera el siguiente Código Interno oficial de Bolidrop (ej. BLD-PRD-000001).
     */
    public static function generateNextInternalCode(): string
    {
        $count = static::withoutGlobalScopes()->count();
        $next = $count + 1;

        $highest = static::withoutGlobalScopes()
            ->where('internal_code', 'LIKE', 'BLD-PRD-%')
            ->orderByRaw('CAST(SUBSTRING(internal_code, 9) AS UNSIGNED) DESC')
            ->value('internal_code');

        if ($highest && preg_match('/BLD-PRD-(\d+)/', $highest, $matches)) {
            $next = max($next, ((int) $matches[1]) + 1);
        }

        do {
            $candidate = 'BLD-PRD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $exists = static::withoutGlobalScopes()->where('internal_code', $candidate)->exists();
            if ($exists) {
                $next++;
            }
        } while ($exists);

        return $candidate;
    }

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

