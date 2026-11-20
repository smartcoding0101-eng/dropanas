<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductBulkImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    /**
     * Listado del Catálogo General / Megabodega para Dropshippers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'provider:id,name,company_or_store_name', 'warehouseInventories.warehouse'])
            ->where('status', 'published');

        // Filtro por categoría
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtro por departamento/bodega (Santa Cruz, La Paz, Cochabamba)
        if ($request->filled('department')) {
            $query->whereHas('warehouseInventories.warehouse', function ($q) use ($request) {
                $q->where('department', $request->department)
                  ->where('stock_available', '>', 0);
            });
        }

        // Búsqueda por nombre o SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Detalle de un producto con desglose de inventario por bodega.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::with(['category', 'provider:id,name,company_or_store_name', 'warehouseInventories.warehouse'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ]);
    }

    /**
     * Creación de producto (Exclusivo para Proveedores).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isProvider() && !$user->isTenantAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Solo los proveedores mayoristas o administradores pueden publicar productos.',
            ], 403);
        }

        // Normalizar booleanos que llegan como string ("true" / "false") desde FormData multipart
        if ($request->has('is_cod_available')) {
            $request->merge([
                'is_cod_available' => filter_var($request->input('is_cod_available'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:200',
            'sku' => 'required|string|max:50',
            'description' => 'nullable|string',
            'cost_price_bob' => 'required|numeric|min:1',
            'suggested_price_bob' => 'required|numeric|gt:cost_price_bob',
            'min_price_bob' => 'required|numeric|gte:cost_price_bob',
            'weight_kg' => 'nullable|numeric|min:0.01',
            'images' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_url' => 'nullable|string|max:500',
            'is_cod_available' => 'nullable|boolean',
            'initial_warehouse_id' => 'nullable|exists:warehouses,id',
            'initial_stock' => 'nullable|integer|min:0',
        ]);

        $images = $validated['images'] ?? [];

        // Procesar subida de imagen física si se adjuntó
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $images[] = '/storage/' . $path;
        } elseif ($request->filled('image_url')) {
            $images[] = $request->input('image_url');
        }

        $validated['images'] = !empty($images) ? $images : null;
        unset($validated['image'], $validated['image_url']);

        $initialWarehouseId = $validated['initial_warehouse_id'] ?? null;
        $initialStock = (int) ($validated['initial_stock'] ?? 0);
        unset($validated['initial_warehouse_id'], $validated['initial_stock']);

        // Si quien publica es tenant_admin, asignar al proveedor del almacén o al proveedor principal
        $providerId = $user->id;
        if (!$user->isProvider()) {
            $whProviderId = $initialWarehouseId ? Warehouse::where('id', $initialWarehouseId)->value('provider_id') : null;
            $providerId = $whProviderId ?? \App\Models\User::where('role', 'provider')->value('id') ?? $user->id;
        }

        $validated['provider_id'] = $providerId;
        $validated['tenant_id'] = $user->tenant_id ?? \App\Models\Tenant::value('id');
        $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']) . '-' . rand(1000, 9999);
        $validated['status'] = 'published';
        $validated['is_cod_available'] = $validated['is_cod_available'] ?? true;

        $product = Product::create($validated);

        if ($initialWarehouseId) {
            $inv = \App\Models\WarehouseInventory::updateOrCreate(
                [
                    'warehouse_id' => $initialWarehouseId,
                    'product_id'   => $product->id,
                ],
                [
                    'tenant_id'       => $validated['tenant_id'],
                    'stock_physical'  => $initialStock,
                    'stock_reserved'  => 0,
                    'stock_available' => $initialStock,
                ]
            );

            if ($initialStock > 0) {
                app(\App\Services\Inventory\KardexService::class)->recordOpeningBalance(
                    $inv,
                    $user->id,
                    'Ingreso inicial por alta y publicación de producto en bodega'
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Producto publicado en el catálogo mayorista con éxito.',
            'data' => $product->load('warehouseInventories.warehouse'),
        ], 201);
    }

    /**
     * Descarga la plantilla oficial para Carga Masiva (Excel / CSV).
     */
    public function downloadTemplate(Request $request, ProductBulkImportService $service): BinaryFileResponse
    {
        $format = strtolower($request->query('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $result = $service->generateTemplate($format);

        return response()->download($result['path'], $result['filename'], [
            'Content-Type' => $result['mime'],
        ])->deleteFileAfterSend(true);
    }

    /**
     * Procesa la carga masiva de productos desde Excel (.xlsx/.xls) o CSV (.csv).
     */
    public function bulkUpload(Request $request, ProductBulkImportService $service): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isProvider() && !$user->isTenantAdmin())) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los proveedores mayoristas o administradores pueden realizar carga masiva.',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'])) {
            return response()->json([
                'success' => false,
                'message' => 'Formato no soportado. Debe ser un archivo Excel (.xlsx, .xls) o CSV (.csv).',
            ], 422);
        }

        $result = $service->processBulkUpload($file, $user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

