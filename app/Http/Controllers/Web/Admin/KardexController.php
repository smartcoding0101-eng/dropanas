<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryKardex;
use App\Models\Product;
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
            ->with(['warehouse', 'product', 'user'])
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

        $records = $query->paginate(25)->withQueryString();

        // Métricas de Inventario Consolidado
        $warehouses = Warehouse::where('tenant_id', $tenantId)->get();
        $products   = Product::where('tenant_id', $tenantId)->get();

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

        return view('admin.kardex.index', compact('records', 'warehouses', 'products', 'metrics'));
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
            ->with(['warehouse', 'product', 'user'])
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
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'kardex_global_dropana_' . date('Ymd_His') . '.csv';

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
                'Condición',
                'Cantidad',
                'Costo Unitario (BOB)',
                'Total Costo (BOB)',
                'Físico Antes',
                'Físico Después',
                'Disponible Antes',
                'Disponible Después',
                'Usuario Responsable',
                'Glosa / Notas',
            ]);

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
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

