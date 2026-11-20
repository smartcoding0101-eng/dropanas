<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\WarehouseSellerReserve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderNetworkController extends Controller
{
    /**
     * Listado de Vendedores (Dropshippers) del tenant con sus métricas.
     */
    public function sellers(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = User::where('role', 'dropshipper')
            ->where('is_active', true)
            ->where('tenant_id', $user->tenant_id);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('company_or_store_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        $sellers = $query->withCount('orders')
            ->orderBy('name')
            ->paginate(15);

        // Añadir información de reservas activas hechas por este proveedor a cada vendedor
        $sellers->getCollection()->transform(function ($seller) use ($user) {
            $activeReservesCount = WarehouseSellerReserve::where('dropshipper_id', $seller->id)
                ->where('reserved_by', $user->id)
                ->where('target_type', 'seller')
                ->active()
                ->sum('quantity');

            $seller->active_reserved_units = (int) $activeReservesCount;
            return $seller;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $sellers,
        ]);
    }

    /**
     * Listado de Clientes (compradores históricos + registrados directamente).
     */
    public function customers(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Customer::where('tenant_id', $user->tenant_id);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('document_id', 'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%")
                  ->orWhere('department', 'like', "%{$s}%");
            });
        }

        if ($request->filled('segment')) {
            $query->where('customer_segment', $request->segment);
        }

        $customers = $query->orderByDesc('total_spent_bob')->paginate(15);

        // Añadir reservas activas hechas a cada cliente
        $customers->getCollection()->transform(function ($cust) use ($user) {
            $activeReservesCount = WarehouseSellerReserve::where('customer_id', $cust->id)
                ->where('reserved_by', $user->id)
                ->where('target_type', 'customer')
                ->active()
                ->sum('quantity');

            $cust->active_reserved_units = (int) $activeReservesCount;
            return $cust;
        });

        return response()->json([
            'status' => 'success',
            'data'   => $customers,
        ]);
    }

    /**
     * Crear cliente directamente por el proveedor.
     */
    public function storeCustomer(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isProvider() && !$user->isTenantAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Sin permisos.'], 403);
        }

        $validated = $request->validate([
            'full_name'       => 'required|string|max:150',
            'phone'           => 'required|string|max:30',
            'document_id'     => 'nullable|string|max:40',
            'email'           => 'nullable|email|max:150',
            'department'      => 'nullable|string|max:60',
            'city'            => 'nullable|string|max:100',
            'default_address' => 'nullable|string|max:300',
        ]);

        // Evitar duplicados por teléfono en el mismo tenant
        $exists = Customer::where('tenant_id', $user->tenant_id)
            ->where('phone', $validated['phone'])
            ->first();

        if ($exists) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ya existe un cliente registrado con ese número de teléfono.',
                'data'    => $exists,
            ], 422);
        }

        $customer = Customer::create(array_merge($validated, [
            'tenant_id'        => $user->tenant_id,
            'customer_segment' => 'new',
            'total_orders'     => 0,
            'completed_orders' => 0,
            'cancelled_orders' => 0,
            'total_spent_bob'  => 0,
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => "Cliente \"{$customer->full_name}\" registrado correctamente.",
            'data'    => $customer,
        ], 201);
    }

    /**
     * Listado consolidado de reservas del proveedor (Vendedores y Clientes).
     */
    public function reserves(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WarehouseSellerReserve::with([
                'product:id,name,sku,internal_code',
                'warehouse:id,name,code,city,department',
                'dropshipper:id,name,company_or_store_name,email,phone',
                'customer:id,full_name,phone,email,city'
            ])
            ->where('tenant_id', $user->tenant_id)
            ->where('reserved_by', $user->id);

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->active();
        }

        $reserves = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data'   => $reserves,
        ]);
    }

    /**
     * Crear una reserva para un Vendedor o un Cliente.
     */
    public function storeReserve(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isProvider() && !$user->isTenantAdmin()) {
            return response()->json(['status' => 'error', 'message' => 'Sin permisos.'], 403);
        }

        $validated = $request->validate([
            'target_type'    => 'required|in:seller,customer',
            'warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'     => 'required|exists:products,id',
            'dropshipper_id' => 'required_if:target_type,seller|nullable|exists:users,id',
            'customer_id'    => 'required_if:target_type,customer|nullable|exists:customers,id',
            'quantity'       => 'required|integer|min:1',
            'notes'          => 'nullable|string|max:400',
            'expires_at'     => 'nullable|date|after:now',
        ]);

        return DB::transaction(function () use ($validated, $user) {
            $inventory = WarehouseInventory::where('warehouse_id', $validated['warehouse_id'])
                ->where('product_id', $validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'El producto seleccionado no tiene inventario en este almacén.',
                ], 422);
            }

            if ($inventory->stock_available < $validated['quantity']) {
                return response()->json([
                    'status'          => 'error',
                    'message'         => "Stock insuficiente en bodega. Disponible: {$inventory->stock_available} unidades.",
                    'stock_available' => $inventory->stock_available,
                ], 422);
            }

            // Descontar disponible y apartar a reservado
            $inventory->stock_available -= $validated['quantity'];
            $inventory->stock_reserved  += $validated['quantity'];
            $inventory->save();

            $reserve = WarehouseSellerReserve::create([
                'tenant_id'      => $user->tenant_id,
                'warehouse_id'   => $validated['warehouse_id'],
                'product_id'     => $validated['product_id'],
                'dropshipper_id' => $validated['target_type'] === 'seller' ? $validated['dropshipper_id'] : null,
                'customer_id'    => $validated['target_type'] === 'customer' ? $validated['customer_id'] : null,
                'target_type'    => $validated['target_type'],
                'reserved_by'    => $user->id,
                'quantity'       => $validated['quantity'],
                'notes'          => $validated['notes'] ?? null,
                'expires_at'     => $validated['expires_at'] ?? null,
                'status'         => 'active',
            ]);

            $destinatario = $validated['target_type'] === 'seller'
                ? (User::find($validated['dropshipper_id'])?->name ?? 'Vendedor')
                : (Customer::find($validated['customer_id'])?->full_name ?? 'Cliente');

            return response()->json([
                'status'  => 'success',
                'message' => "{$validated['quantity']} unidades apartadas exitosamente para \"{$destinatario}\".",
                'data'    => $reserve->load(['product', 'warehouse', 'dropshipper', 'customer']),
            ], 201);
        });
    }

    /**
     * Liberar una reserva de stock (devuelve unidades al stock disponible).
     */
    public function releaseReserve(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $reserve = WarehouseSellerReserve::where('id', $id)
            ->where('tenant_id', $user->tenant_id)
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
}
