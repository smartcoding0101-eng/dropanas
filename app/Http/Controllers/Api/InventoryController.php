<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryAlert;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /** Listar inventario general consolidado por bodega */
    public function index(Request $request): JsonResponse
    {
        $query = WarehouseInventory::with(['product', 'warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('low_stock')) {
            $query->where('stock_available', '<=', 10);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->paginate(20),
        ]);
    }

    /** Transferir stock físico entre dos bodegas bolivianas */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'        => 'nullable|exists:products,id',
            'quantity'          => 'nullable|integer|min:1',
            'items'             => 'nullable|array|min:1',
            'items.*.product_id'=> 'required_with:items|exists:products,id',
            'items.*.quantity'  => 'required_with:items|integer|min:1',
            'notes'             => 'nullable|string|max:500',
        ]);

        // Normalizar a una lista de items
        $items = [];
        if (!empty($validated['items'])) {
            $items = $validated['items'];
        } elseif (!empty($validated['product_id']) && !empty($validated['quantity'])) {
            $items[] = [
                'product_id' => $validated['product_id'],
                'quantity'   => (int) $validated['quantity'],
            ];
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Debes especificar al menos un producto y cantidad a transferir.',
            ], 422);
        }

        $user = $request->user();

        return DB::transaction(function () use ($validated, $items, $user) {
            $transfers = [];

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qty = (int) $item['quantity'];

                $sourceInventory = WarehouseInventory::where('product_id', $productId)
                    ->where('warehouse_id', $validated['from_warehouse_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$sourceInventory || $sourceInventory->stock_available < $qty) {
                    $prodName = Product::find($productId)?->name ?? $productId;
                    return response()->json([
                        'status'  => 'error',
                        'message' => "Stock insuficiente para el producto \"{$prodName}\" en la bodega de origen. Disponible: " . ($sourceInventory ? $sourceInventory->stock_available : 0),
                        'stock_available' => $sourceInventory ? $sourceInventory->stock_available : 0,
                    ], 422);
                }

                // Descontar origen
                $sourceInventory->stock_physical -= $qty;
                $sourceInventory->stock_available -= $qty;
                $sourceInventory->save();

                // Acreditar destino
                $targetInventory = WarehouseInventory::firstOrCreate(
                    [
                        'tenant_id'    => $user->tenant_id,
                        'warehouse_id' => $validated['to_warehouse_id'],
                        'product_id'   => $productId,
                    ],
                    [
                        'stock_physical'  => 0,
                        'stock_reserved'  => 0,
                        'stock_available' => 0,
                    ]
                );

                $targetInventory->stock_physical += $qty;
                $targetInventory->stock_available += $qty;
                $targetInventory->save();

                // Registrar movimiento de auditoría
                $transfer = StockTransfer::create([
                    'tenant_id'         => $user->tenant_id,
                    'product_id'        => $productId,
                    'from_warehouse_id' => $validated['from_warehouse_id'],
                    'to_warehouse_id'   => $validated['to_warehouse_id'],
                    'requested_by'      => $user->id,
                    'approved_by'       => $user->id,
                    'quantity'          => $qty,
                    'status'            => 'completed',
                    'notes'             => $validated['notes'] ?? 'Transferencia interdepartamental directa',
                    'completed_at'      => now(),
                ]);

                // Trazabilidad Kardex Global: Registrar salida origen y entrada destino
                $product = Product::find($productId);
                if ($product) {
                    app(\App\Services\Inventory\KardexService::class)->recordStockTransfer(
                        $sourceInventory,
                        $targetInventory,
                        $product,
                        $qty,
                        $transfer->id,
                        $user->id,
                        $validated['notes'] ?? null
                    );
                }

                $transfers[] = $transfer;
            }

            return response()->json([
                'status'  => 'success',
                'message' => count($transfers) > 1 
                    ? 'Transferencia múltiple de ' . count($transfers) . ' productos completada exitosamente.'
                    : 'Transferencia de inventario completada exitosamente.',
                'data'    => count($transfers) === 1 ? $transfers[0]->load(['fromWarehouse', 'toWarehouse', 'product']) : $transfers,
            ], 201);
        });
    }

    /** Consultar o configurar alertas de stock mínimo */
    public function alerts(Request $request): JsonResponse
    {
        $alerts = InventoryAlert::with(['product', 'warehouse'])
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $alerts,
        ]);
    }

    /** Configurar alerta de stock mínimo */
    public function setAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'           => 'required|exists:products,id',
            'warehouse_id'         => 'required|exists:warehouses,id',
            'min_stock_threshold'  => 'required|integer|min:1',
            'reorder_quantity'     => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $alert = InventoryAlert::updateOrCreate(
            [
                'warehouse_id' => $validated['warehouse_id'],
                'product_id'   => $validated['product_id'],
            ],
            [
                'tenant_id'            => $user->tenant_id,
                'min_stock_threshold'  => $validated['min_stock_threshold'],
                'reorder_quantity'     => $validated['reorder_quantity'],
                'is_active'            => true,
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Alerta de inventario configurada correctamente.',
            'data'    => $alert,
        ], 200);
    }
}

