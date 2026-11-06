<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryKardex extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'inventory_kardex';

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_id',
        'user_id',
        'movement_category',
        'movement_type',
        'reference_type',
        'reference_id',
        'document_number',
        'quantity',
        'unit_cost_bob',
        'total_cost_bob',
        'stock_physical_before',
        'stock_physical_after',
        'stock_reserved_before',
        'stock_reserved_after',
        'stock_available_before',
        'stock_available_after',
        'condition',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost_bob' => 'decimal:2',
        'total_cost_bob' => 'decimal:2',
        'stock_physical_before' => 'integer',
        'stock_physical_after' => 'integer',
        'stock_reserved_before' => 'integer',
        'stock_reserved_after' => 'integer',
        'stock_available_before' => 'integer',
        'stock_available_after' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
