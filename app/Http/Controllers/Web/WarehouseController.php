<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\WarehouseSellerReserve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    // =========================================================================
    // CRUD DE ALMACENES
    // =========================================================================

    /**
     * Listar almacenes del proveedor autenticado con estadísticas de inventario.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $warehouses = Warehouse::withCount([
                'inventories as total_products_count' => fn($q) => $q->where('stock_available', '>', 0),
            ])
            ->withSum('inventories as total_stock', 'stock_available')
            ->withSum('inventories as total_reserved', 'stock_reserved')
            ->when($user->isProvider(), fn($q) => $q->where('provider_id', $user->id))
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $warehouses,
        ]);
    }

    /**
     * Crear un nuevo almacén propio del proveedor.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isProvider() && !$user->isTenantAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Sin permisos para crear almacenes.'], 403);
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:120',
            'code'          => 'required|string|max:30|alpha_dash',
            'department'    => 'required|string|max:50',
            'city'          => 'required|string|max:100',
            'address'       => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
        ]);

        // Verificar unicidad del código por tenant
        $exists = Warehouse::where('tenant_id', $user->tenant_id)
            ->where('code', strtoupper($validated['code']))
            ->exists();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El código de almacén ya existe. Use un código único.',
                'errors'  => ['code' => ['El código ya está en uso.']],
            ], 422);
        }

        $warehouse = Warehouse::create([
            'tenant_id'     => $user->tenant_id,
            'provider_id'   => $user->isProvider() ? $user->id : null,
            'name'          => $validated['name'],
            'code'          => strtoupper($validated['code']),
            'department'    => $validated['department'],
            'city'          => $validated['city'],
            'address'       => $validated['address'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'latitude'      => $validated['latitude'] ?? null,
            'longitude'     => $validated['longitude'] ?? null,
            'is_active'     => true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Almacén \"{$warehouse->name}\" creado exitosamente.",
            'data'    => $warehouse,
        ], 201);
    }

    /**
     * Actualizar datos de un almacén existente.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($id, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $validated = $request->validate([
            'name'          => 'sometimes|required|string|max:120',
            'department'    => 'sometimes|required|string|max:50',
            'city'          => 'sometimes|required|string|max:100',
            'address'       => 'sometimes|required|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'is_active'     => 'sometimes|boolean',
        ]);

        $warehouse->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => "Almacén \"{$warehouse->name}\" actualizado correctamente.",
            'data'    => $warehouse->fresh(),
        ]);
    }

    /**
     * Eliminar un almacén (solo si no tiene inventario ni reservas activas).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($id, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $totalStock = $warehouse->inventories()->sum('stock_available');
        if ($totalStock > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "No se puede eliminar: el almacén tiene {$totalStock} unidades de stock activo. Transfiera el stock primero.",
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Almacén eliminado correctamente.',
        ]);
    }

    // =========================================================================
    // INVENTARIO Y PRODUCTOS DEL ALMACÉN
    // =========================================================================

    /**
     * Listar productos con inventario en un almacén específico.
     */
    public function products(Request $request, string $id): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($id, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $query = WarehouseInventory::with(['product.category'])
            ->where('warehouse_id', $id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('product', fn($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('sku', 'like', "%{$s}%")
                ->orWhere('internal_code', 'like', "%{$s}%")
            );
        }

        if ($request->filled('visible_public')) {
            $query->where('visible_public', filter_var($request->visible_public, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('visible_sellers')) {
            $query->where('visible_sellers', filter_var($request->visible_sellers, FILTER_VALIDATE_BOOLEAN));
        }

        $inventories = $query->orderByDesc('stock_available')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $inventories,
        ]);
    }

    // =========================================================================
    // VISIBILIDAD DE PRODUCTOS (por almacén)
    // =========================================================================

    /**
     * Activar o desactivar visibilidad de un producto en un almacén.
     * Acepta: visible_public (bool), visible_sellers (bool)
     */
    public function toggleVisibility(Request $request, string $warehouseId, string $inventoryId): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($warehouseId, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $inventory = WarehouseInventory::where('id', $inventoryId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            return response()->json(['status' => 'error', 'message' => 'Registro de inventario no encontrado.'], 404);
        }

        $validated = $request->validate([
            'visible_public'  => 'required_without:visible_sellers|boolean',
            'visible_sellers' => 'required_without:visible_public|boolean',
        ]);

        $inventory->update($validated);

        $product = $inventory->product;
        $changes = [];
        if (array_key_exists('visible_public', $validated)) {
            $changes[] = 'Catálogo público: ' . ($validated['visible_public'] ? '✅ Visible' : '🔴 Oculto');
        }
        if (array_key_exists('visible_sellers', $validated)) {
            $changes[] = 'Vendedores: ' . ($validated['visible_sellers'] ? '✅ Visible' : '🔴 Oculto');
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Visibilidad de \"{$product->name}\" actualizada: " . implode(', ', $changes),
            'data'    => $inventory->fresh(),
        ]);
    }

    /**
     * Togglear visibilidad en lote (múltiples inventarios a la vez).
     */
    public function batchToggleVisibility(Request $request, string $warehouseId): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($warehouseId, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $validated = $request->validate([
            'inventory_ids'   => 'required|array|min:1',
            'inventory_ids.*' => 'uuid',
            'visible_public'  => 'required_without:visible_sellers|boolean',
            'visible_sellers' => 'required_without:visible_public|boolean',
        ]);

        $updateData = [];
        if (array_key_exists('visible_public', $validated)) {
            $updateData['visible_public'] = $validated['visible_public'];
        }
        if (array_key_exists('visible_sellers', $validated)) {
            $updateData['visible_sellers'] = $validated['visible_sellers'];
        }

        $count = WarehouseInventory::where('warehouse_id', $warehouseId)
            ->whereIn('id', $validated['inventory_ids'])
            ->update($updateData);

        return response()->json([
            'status'  => 'success',
            'message' => "{$count} productos actualizados en lote.",
        ]);
    }

    // =========================================================================
    // RESERVAS DE STOCK PARA VENDEDORES
    // =========================================================================

    /**
     * Listar reservas activas de un almacén.
     */
    public function reserves(Request $request, string $id): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($id, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $reserves = WarehouseSellerReserve::with(['product:id,name,sku,internal_code', 'dropshipper:id,name,company_or_store_name,email'])
            ->where('warehouse_id', $id)
            ->active()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $reserves,
        ]);
    }

    /**
     * Crear una reserva de stock para un vendedor específico.
     * Descuenta stock_available y sube stock_reserved en warehouse_inventories.
     */
    public function storeReserve(Request $request, string $id): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($id, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $validated = $request->validate([
            'product_id'      => 'required|exists:products,id',
            'dropshipper_id'  => 'required|exists:users,id',
            'quantity'        => 'required|integer|min:1',
            'notes'           => 'nullable|string|max:400',
            'expires_at'      => 'nullable|date|after:now',
        ]);

        // Verificar que el dropshipper exista y sea del rol correcto
        $dropshipper = User::find($validated['dropshipper_id']);
        if (!$dropshipper || !$dropshipper->isDropshipper()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El usuario seleccionado no es un vendedor/dropshipper válido.',
            ], 422);
        }

        return DB::transaction(function () use ($validated, $user, $id) {
            $inventory = WarehouseInventory::where('warehouse_id', $id)
                ->where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El producto no tiene inventario en este almacén.',
                ], 422);
            }

            if ($inventory->stock_available < $validated['quantity']) {
                return response()->json([
                    'status'           => 'error',
                    'message'          => "Stock insuficiente. Disponible: {$inventory->stock_available} unidades.",
                    'stock_available'  => $inventory->stock_available,
                ], 422);
            }

            // Descontar stock disponible y sumar a reservado
            $inventory->stock_available -= $validated['quantity'];
            $inventory->stock_reserved  += $validated['quantity'];
            $inventory->save();

            $reserve = WarehouseSellerReserve::create([
                'tenant_id'      => $user->tenant_id,
                'warehouse_id'   => $id,
                'product_id'     => $validated['product_id'],
                'dropshipper_id' => $validated['dropshipper_id'],
                'reserved_by'    => $user->id,
                'quantity'       => $validated['quantity'],
                'notes'          => $validated['notes'] ?? null,
                'expires_at'     => $validated['expires_at'] ?? null,
                'status'         => 'active',
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => "{$validated['quantity']} unidades reservadas para \"{$reserve->dropshipper->name}\" exitosamente.",
                'data'    => $reserve->load(['product:id,name,sku,internal_code', 'dropshipper:id,name,company_or_store_name']),
            ], 201);
        });
    }

    /**
     * Liberar (cancelar) una reserva y devolver el stock disponible.
     */
    public function releaseReserve(Request $request, string $warehouseId, string $reserveId): JsonResponse
    {
        $user      = $request->user();
        $warehouse = $this->findOwnWarehouse($warehouseId, $user);

        if (!$warehouse) {
            return response()->json(['status' => 'error', 'message' => 'Almacén no encontrado o sin permisos.'], 404);
        }

        $reserve = WarehouseSellerReserve::where('id', $reserveId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->first();

        if (!$reserve) {
            return response()->json(['status' => 'error', 'message' => 'Reserva no encontrada o ya liberada.'], 404);
        }

        return DB::transaction(function () use ($reserve) {
            $inventory = WarehouseInventory::where('warehouse_id', $reserve->warehouse_id)
                ->where('product_id', $reserve->product_id)
                ->lockForUpdate()
                ->first();

            if ($inventory) {
                $inventory->stock_available += $reserve->quantity;
                $inventory->stock_reserved  = max(0, $inventory->stock_reserved - $reserve->quantity);
                $inventory->save();
            }

            $reserve->update(['status' => 'released']);

            return response()->json([
                'status'  => 'success',
                'message' => "Reserva liberada: {$reserve->quantity} unidades devueltas al stock disponible.",
            ]);
        });
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    /**
     * Busca un almacén verificando que pertenece al proveedor (o al admin del tenant).
     */
    private function findOwnWarehouse(string $id, $user): ?Warehouse
    {
        $query = Warehouse::where('id', $id)
            ->where('tenant_id', $user->tenant_id);

        if ($user->isProvider()) {
            $query->where('provider_id', $user->id);
        }

        return $query->first();
    }
}
