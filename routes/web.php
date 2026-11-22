<?php

use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\PanelController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// =========================================================================
// 1. ZONA PÚBLICA: Landing Comercial y Autenticación (Login / Registro)
// =========================================================================
Route::get('/', function () {
    return view('welcome');
});

// Rutas de Autenticación Web (Con Rate-Limiting contra fuerza bruta)
Route::get('/login', [\App\Http\Controllers\Web\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\Web\AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/registro', [\App\Http\Controllers\Web\AuthController::class, 'showRegister'])->name('register');
Route::post('/registro', [\App\Http\Controllers\Web\AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/logout', [\App\Http\Controllers\Web\AuthController::class, 'logout'])->name('logout');

// Servir imágenes públicas de productos (Garantiza visualización en cPanel sin depender de symlink)
Route::get('/storage/products/{filename}', function (string $filename) {
    $path = storage_path('app/public/products/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
})->where('filename', '.*');

// Descarga de Plantilla Oficial de Carga Masiva (Excel / CSV)
Route::get('/products/template/download', [\App\Http\Controllers\Api\ProductController::class, 'downloadTemplate'])->name('products.template.download');

// =========================================================================
// 2. ZONA PROTEGIDA ZERO-TRUST: Requiere Usuario Registrado y Autenticado
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // Catálogo Mayorista de Productos (Megabodega Nacional)
    Route::get('/catalogo', function () {
        $products = Product::with(['category', 'provider', 'warehouseInventories.warehouse'])
            ->where('status', 'published')
            ->where('is_cod_available', true)
            ->get();
        $categories = \App\Models\Category::withCount('products')->orderBy('name')->get();
        return view('catalog', compact('products', 'categories'));
    })->name('catalog');

    // =========================================================================
    // MÓDULO ALMACENES — CRUD + Productos + Visibilidad + Reservas de Stock
    // =========================================================================
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/',                                             [\App\Http\Controllers\Web\WarehouseController::class, 'index'])               ->name('index');
        Route::post('/',                                            [\App\Http\Controllers\Web\WarehouseController::class, 'store'])               ->name('store');
        Route::put('/{id}',                                         [\App\Http\Controllers\Web\WarehouseController::class, 'update'])              ->name('update');
        Route::delete('/{id}',                                      [\App\Http\Controllers\Web\WarehouseController::class, 'destroy'])             ->name('destroy');

        // Productos e inventario del almacén
        Route::get('/{id}/products',                                [\App\Http\Controllers\Web\WarehouseController::class, 'products'])            ->name('products');

        // Visibilidad por almacén (individual y lote)
        Route::patch('/{warehouseId}/products/{inventoryId}/visibility', [\App\Http\Controllers\Web\WarehouseController::class, 'toggleVisibility'])  ->name('visibility');
        Route::patch('/{warehouseId}/products/batch-visibility',    [\App\Http\Controllers\Web\WarehouseController::class, 'batchToggleVisibility'])->name('batch.visibility');

        // Reservas de stock para vendedores
        Route::get('/{id}/reserves',                                [\App\Http\Controllers\Web\WarehouseController::class, 'reserves'])            ->name('reserves');
        Route::post('/{id}/reserves',                               [\App\Http\Controllers\Web\WarehouseController::class, 'storeReserve'])        ->name('reserves.store');
        Route::patch('/{warehouseId}/reserves/{reserveId}/release', [\App\Http\Controllers\Web\WarehouseController::class, 'releaseReserve'])      ->name('reserves.release');
    });

    // =========================================================================
    // MÓDULO RED COMERCIAL (Vendedores & Clientes del Proveedor + Reservas)
    // =========================================================================
    Route::prefix('provider/network')->name('provider.network.')->group(function () {
        Route::get('/sellers',               [\App\Http\Controllers\Web\ProviderNetworkController::class, 'sellers'])        ->name('sellers');
        Route::get('/customers',             [\App\Http\Controllers\Web\ProviderNetworkController::class, 'customers'])      ->name('customers');
        Route::post('/customers',            [\App\Http\Controllers\Web\ProviderNetworkController::class, 'storeCustomer'])  ->name('customers.store');
        Route::get('/reserves',              [\App\Http\Controllers\Web\ProviderNetworkController::class, 'reserves'])       ->name('reserves');
        Route::post('/reserves',             [\App\Http\Controllers\Web\ProviderNetworkController::class, 'storeReserve'])   ->name('reserves.store');
        Route::patch('/reserves/{id}/release', [\App\Http\Controllers\Web\ProviderNetworkController::class, 'releaseReserve'])->name('reserves.release');
    });

    // Exportación de Kardex del Proveedor (CSV con UTF-8 BOM para Excel)
    Route::get('/panel/proveedor/kardex/export', [\App\Http\Controllers\Web\PanelController::class, 'exportProviderKardex'])->name('provider.kardex.export');

    // CRUD Web de Categorías y Creación de Productos (Sesión Web directa para Panel Proveedor / Admin)
    Route::post('/products/bulk-upload', [\App\Http\Controllers\Api\ProductController::class, 'bulkUpload'])->name('products.bulk.upload');
    Route::post('/products', [\App\Http\Controllers\Api\ProductController::class, 'store'])->name('products.store.web');
    Route::post('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->name('products.update.post.web');
    Route::match(['put', 'patch'], '/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->name('products.update.web');
    Route::delete('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->name('products.destroy.web');
    Route::post('/inventory/transfer', [\App\Http\Controllers\Api\InventoryController::class, 'transfer'])->name('inventory.transfer.web');
    Route::post('/inventory/alerts', [\App\Http\Controllers\Api\InventoryController::class, 'setAlert'])->name('inventory.alerts.web');
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'store'])->name('categories.store');
    Route::match(['put', 'patch'], '/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy'])->name('categories.destroy');

    // Checkout Demo COD + QR
    Route::get('/checkout-demo', [CheckoutController::class, 'index'])->name('checkout.demo');

    // Paneles de usuario por rol
    Route::get('/panel/dropshipper', [PanelController::class, 'dropshipper'])->name('panel.dropshipper');
    Route::get('/panel/proveedor', [PanelController::class, 'proveedor'])->name('panel.proveedor');
    Route::get('/panel/admin', [PanelController::class, 'admin'])->name('panel.admin');
    Route::get('/panel/carrier', [PanelController::class, 'carrier'])->name('panel.carrier');
    Route::get('/panel/fulfillment', [PanelController::class, 'fulfillment'])->name('panel.fulfillment');

    // Impresión de Rótulos, Etiquetas Térmicas (Zebra 4x6 / A6) y Notas de Entrega
    Route::get('/orders/{id}/label', [\App\Http\Controllers\Web\ShippingLabelController::class, 'show'])->name('orders.label');
    Route::get('/orders/{id}/delivery-note', [\App\Http\Controllers\Web\ShippingLabelController::class, 'deliveryNote'])->name('orders.delivery_note');
    Route::get('/orders/labels/batch', [\App\Http\Controllers\Web\ShippingLabelController::class, 'batch'])->name('orders.labels.batch');

    // Kardex Global de Inventario y Trazabilidad (Exclusivo Administrador del Sistema)
    Route::middleware(['admin'])->prefix('panel/admin/kardex')->name('admin.kardex.')->group(function () {
        Route::get('/',        [\App\Http\Controllers\Web\Admin\KardexController::class, 'index']) ->name('index');
        Route::post('/adjust', [\App\Http\Controllers\Web\Admin\KardexController::class, 'adjust'])->name('adjust');
        Route::get('/export',  [\App\Http\Controllers\Web\Admin\KardexController::class, 'export'])->name('export');
    });

    // Configuración Manual o Sincronización de Tipo de Cambio BCB (Admin)
    Route::post('/panel/admin/exchange-rate', [PanelController::class, 'updateExchangeRate'])->name('admin.exchange-rate.update');

    // Liquidaciones Bancarias Bolivia (Admin)
    Route::prefix('settlements')->name('settlements.')->group(function () {
        Route::get('/',                [\App\Http\Controllers\Web\BankSettlementController::class, 'index'])    ->name('index');
        Route::post('/',               [\App\Http\Controllers\Web\BankSettlementController::class, 'store'])    ->name('store');
        Route::get('/{id}',            [\App\Http\Controllers\Web\BankSettlementController::class, 'show'])     ->name('show');
        Route::patch('/{id}/process',  [\App\Http\Controllers\Web\BankSettlementController::class, 'markProcessing'])->name('processing');
        Route::patch('/{id}/complete', [\App\Http\Controllers\Web\BankSettlementController::class, 'complete']) ->name('complete');
        Route::delete('/{id}/reject',  [\App\Http\Controllers\Web\BankSettlementController::class, 'reject'])   ->name('reject');
    });

    // Reportes & Analytics Dashboard (Admin)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/',                      [\App\Http\Controllers\Web\ReportController::class, 'index'])          ->name('index');
        Route::get('/api/daily-series',       [\App\Http\Controllers\Web\ReportController::class, 'dailySeriesJson'])->name('api.daily');
        Route::get('/api/kpis',               [\App\Http\Controllers\Web\ReportController::class, 'kpisJson'])       ->name('api.kpis');
    });

    // Logística Inversa & Devoluciones COD (Reverse Logistics)
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/',                       [\App\Http\Controllers\Web\ReturnsManagementController::class, 'index'])        ->name('index');
        Route::post('/{id}/novedad',          [\App\Http\Controllers\Web\ReturnsManagementController::class, 'recordNovedad'])->name('novedad');
        Route::post('/{id}/reschedule',       [\App\Http\Controllers\Web\ReturnsManagementController::class, 'reschedule'])   ->name('reschedule');
        Route::post('/{id}/process',          [\App\Http\Controllers\Web\ReturnsManagementController::class, 'processReturn'])->name('process');
    });

    // Notificaciones WhatsApp Transaccionales (Bolivia +591)
    Route::prefix('notifications/whatsapp')->name('notifications.whatsapp.')->group(function () {
        Route::get('/',                   [\App\Http\Controllers\Web\WhatsAppNotificationController::class, 'index'])  ->name('index');
        Route::post('/{orderId}/send',    [\App\Http\Controllers\Web\WhatsAppNotificationController::class, 'send'])   ->name('send');
        Route::get('/{orderId}/link',     [\App\Http\Controllers\Web\WhatsAppNotificationController::class, 'getLink'])->name('link');
    });

    // Cobro QR/Efectivo en Campo para el Repartidor
    Route::get('/carrier/cobro', [PanelController::class, 'carrierCobro'])->name('carrier.cobro');
    Route::get('/carrier/cobro/{guide}', [PanelController::class, 'carrierCobro'])->name('carrier.cobro.guide');

    // Tracking de pedidos, Soporte y Almacenes (Internos tras autenticación)
    Route::get('/tracking', function () {
        return view('tracking');
    })->name('tracking.index');

    Route::get('/tracking/{guide}', function (string $guide) {
        return view('tracking', ['prefillGuide' => $guide]);
    })->name('tracking.show');

    Route::get('/soporte', function () {
        return view('soporte');
    })->name('soporte');

    Route::get('/contactenos', function () {
        return view('contactenos');
    })->name('contactenos');
});


