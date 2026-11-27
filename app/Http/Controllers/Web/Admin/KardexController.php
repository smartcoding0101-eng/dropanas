<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryKardex;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\Inventory\KardexService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KardexController extends Controller
{
    public function __construct(
        protected KardexService $kardexService
    ) {}

    /**
     * Vista general del Kardex Global para Administradores.
     */
    public function index(Request $request): View
    {
        $tenantId = auth()->user()->tenant_id;

        $query = InventoryKardex::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['warehouse', 'product', 'user', 'order.dropshipper'])
            ->latest('created_at');

        // Filtros
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        // Filtro por Vendedor / Usuario responsable
        if ($request->filled('user_id')) {
            $userId = $request->user_id;
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('order', function ($oq) use ($userId) {
                      $oq->where('dropshipper_id', $userId);
                  });
            });
        }

        // Filtro por Comprador (Cliente Final del Pedido COD)
        if ($request->filled('customer')) {
            $c = trim($request->customer);
            $query->whereHas('order', function ($oq) use ($c) {
                $oq->where('customer_name', 'like', "%{$c}%")
                   ->orWhere('customer_phone', 'like', "%{$c}%")
                   ->orWhere('customer_document_id', 'like', "%{$c}%");
            });
        }

        // Filtro por Tipo de Interviniente (Vendedor, Comprador o Interno)
        if ($request->filled('user_type')) {
            if ($request->user_type === 'seller_order') {
                $query->whereHas('order', fn($oq) => $oq->whereNotNull('dropshipper_id'));
            } elseif ($request->user_type === 'customer_order') {
                $query->whereHas('order', fn($oq) => $oq->whereNotNull('customer_name'));
            } elseif ($request->user_type === 'internal') {
                $query->where(function ($q) {
                    $q->where('reference_type', '!=', 'order')->orWhereNull('reference_type');
                });
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('document_number', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%");
                  })
                  ->orWhereHas('order', function ($oq) use ($s) {
                      $oq->where('customer_name', 'like', "%{$s}%")
                         ->orWhere('customer_phone', 'like', "%{$s}%")
                         ->orWhere('customer_document_id', 'like', "%{$s}%")
                         ->orWhereHas('dropshipper', function ($dq) use ($s) {
                             $dq->where('name', 'like', "%{$s}%")
                                ->orWhere('company_or_store_name', 'like', "%{$s}%");
                         });
                  });
            });
        }

        $perPage = (int) $request->input('per_page', 30);
        if (!in_array($perPage, [15, 30, 50, 100])) {
            $perPage = 30;
        }

        $records = $query->paginate($perPage)->withQueryString();

        // Métricas de Inventario Consolidado
        $warehouses = Warehouse::where('tenant_id', $tenantId)->get();
        $products   = Product::where('tenant_id', $tenantId)->get();

        // Usuarios y Vendedores Dropshippers para Filtro
        $dropshippers = User::where('tenant_id', $tenantId)
            ->where('role', 'dropshipper')
            ->orderBy('name')
            ->get();

        $operators = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['tenant_admin', 'superadmin', 'admin', 'provider', 'carrier_agent'])
            ->orderBy('name')
            ->get();

        $inventories = WarehouseInventory::where('tenant_id', $tenantId)
            ->with('product')
            ->get();

        $totalPhysical  = $inventories->sum('stock_physical');
        $totalReserved  = $inventories->sum('stock_reserved');
        $totalAvailable = $inventories->sum('stock_available');

        $totalValuation = $inventories->sum(function ($inv) {
            $cost = $inv->product ? (float) $inv->product->cost_price_bob : 0;
            return $inv->stock_physical * $cost;
        });

        // Métricas del Mes Actual en Kardex
        $currentMonth = Carbon::now()->startOfMonth();
        $inboundThisMonth = InventoryKardex::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $currentMonth)
            ->where('quantity', '>', 0)
            ->sum('quantity');

        $outboundThisMonth = InventoryKardex::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '>=', $currentMonth)
            ->where('quantity', '<', 0)
            ->sum('quantity');

        $damagedTotal = InventoryKardex::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('condition', 'damaged')
            ->count();

        $metrics = [
            'total_physical'     => $totalPhysical,
            'total_reserved'     => $totalReserved,
            'total_available'    => $totalAvailable,
            'total_valuation'    => $totalValuation,
            'inbound_month'      => $inboundThisMonth,
            'outbound_month'     => abs($outboundThisMonth),
            'damaged_count'      => $damagedTotal,
            'total_transactions' => InventoryKardex::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
        ];

        return view('admin.kardex.index', compact('records', 'warehouses', 'products', 'metrics', 'perPage', 'dropshippers', 'operators'));
    }

    /**
     * Realizar ajuste manual de inventario físico (Auditoría Admin).
     */
    public function adjust(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id'   => 'required|exists:warehouses,id',
            'product_id'     => 'required|exists:products,id',
            'quantity_delta' => 'required|integer|not_in:0',
            'reason'         => 'required|string|max:500',
            'condition'      => 'required|in:optimal,damaged,defective',
        ]);

        $admin = auth()->user();

        try {
            $this->kardexService->recordManualAdjustment(
                $admin->tenant_id,
                $validated['warehouse_id'],
                $validated['product_id'],
                (int) $validated['quantity_delta'],
                $validated['reason'],
                $admin->id,
                $validated['condition']
            );

            return back()->with('success', 'Ajuste manual de inventario registrado correctamente en el Kardex Global.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Error al aplicar el ajuste: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Exportar movimientos filtrados a formato CSV de auditoría contable.
     */
    public function export(Request $request): StreamedResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $query = InventoryKardex::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with(['warehouse', 'product', 'user', 'order.dropshipper'])
            ->orderBy('created_at', 'asc');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('user_id')) {
            $userId = $request->user_id;
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('order', function ($oq) use ($userId) {
                      $oq->where('dropshipper_id', $userId);
                  });
            });
        }
        if ($request->filled('customer')) {
            $c = trim($request->customer);
            $query->whereHas('order', function ($oq) use ($c) {
                $oq->where('customer_name', 'like', "%{$c}%")
                   ->orWhere('customer_phone', 'like', "%{$c}%")
                   ->orWhere('customer_document_id', 'like', "%{$c}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('document_number', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        $filename = 'kardex_global_Bolidrop_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($handle, [
                'ID Kardex',
                'Fecha / Hora',
                'Bodega',
                'Ciudad',
                'Producto',
                'SKU',
                'Categoría Mov.',
                'Tipo Movimiento',
                'Documento Ref.',
                'Nro Documento',
                'Vendedor Dropshipper',
                'Comprador (Cliente Final)',
                'Condición',
                'Cantidad',
                'Costo Unitario (BOB)',
                'Total Costo (BOB)',
                'Físico Antes',
                'Físico Después',
                'Disponible Antes',
                'Disponible Después',
                'Usuario Responsable / Operador',
                'Glosa / Notas',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $vendedor = $row->order?->dropshipper?->company_or_store_name ?? $row->order?->dropshipper?->name ?? 'N/A';
                    $comprador = $row->order?->customer_name ?? 'N/A';
                    if ($row->order && $row->order->customer_phone) {
                        $comprador .= ' (' . $row->order->customer_phone . ')';
                    }

                    fputcsv($handle, [
                        $row->id,
                        $row->created_at->format('Y-m-d H:i:s'),
                        $row->warehouse ? $row->warehouse->name : 'N/A',
                        $row->warehouse ? $row->warehouse->city : 'N/A',
                        $row->product ? $row->product->name : 'N/A',
                        $row->product ? $row->product->sku : 'N/A',
                        $row->movement_category,
                        $row->movement_type,
                        $row->reference_type ?? 'N/A',
                        $row->document_number ?? 'N/A',
                        $vendedor,
                        $comprador,
                        $row->condition,
                        $row->quantity,
                        number_format((float)$row->unit_cost_bob, 2, '.', ''),
                        number_format((float)$row->total_cost_bob, 2, '.', ''),
                        $row->stock_physical_before,
                        $row->stock_physical_after,
                        $row->stock_available_before,
                        $row->stock_available_after,
                        $row->user ? $row->user->name : 'Sistema Automático',
                        $row->notes,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}


