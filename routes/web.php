<?php

use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\PanelController;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

// Página principal
Route::get('/', function () {
    return view('welcome');
});

// Catálogo público de productos (megabodega)
Route::get('/catalogo', function () {
    $products = Product::with(['category', 'warehouseInventories.warehouse'])
        ->where('status', 'published')
        ->where('is_cod_available', true)
        ->get();
    return view('catalog', compact('products'));
});

// Checkout Demo COD + QR
Route::get('/checkout-demo', [CheckoutController::class, 'index'])->name('checkout.demo');

// Rutas de Autenticación Web
Route::get('/login', [\App\Http\Controllers\Web\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\Web\AuthController::class, 'login']);
Route::get('/registro', [\App\Http\Controllers\Web\AuthController::class, 'showRegister'])->name('register');
Route::post('/registro', [\App\Http\Controllers\Web\AuthController::class, 'register']);
Route::post('/logout', [\App\Http\Controllers\Web\AuthController::class, 'logout'])->name('logout');

// Paneles de usuario
Route::get('/panel/dropshipper', [PanelController::class, 'dropshipper'])->name('panel.dropshipper');
Route::get('/panel/proveedor', [PanelController::class, 'proveedor'])->name('panel.proveedor');
Route::get('/panel/admin', [PanelController::class, 'admin'])->name('panel.admin');
Route::get('/panel/carrier', [PanelController::class, 'carrier'])->name('panel.carrier');

// Impresión de Rótulos, Etiquetas Térmicas (Zebra 4x6 / A6) y Notas de Entrega
Route::get('/orders/{id}/label', [\App\Http\Controllers\Web\ShippingLabelController::class, 'show'])->name('orders.label');
Route::get('/orders/{id}/delivery-note', [\App\Http\Controllers\Web\ShippingLabelController::class, 'deliveryNote'])->name('orders.delivery_note');
Route::get('/orders/labels/batch', [\App\Http\Controllers\Web\ShippingLabelController::class, 'batch'])->name('orders.labels.batch');

// Kardex Global de Inventario y Trazabilidad (Exclusivo Administrador del Sistema)
Route::middleware(['auth', 'admin'])->prefix('panel/admin/kardex')->name('admin.kardex.')->group(function () {
    Route::get('/',        [\App\Http\Controllers\Web\Admin\KardexController::class, 'index']) ->name('index');
    Route::post('/adjust', [\App\Http\Controllers\Web\Admin\KardexController::class, 'adjust'])->name('adjust');
    Route::get('/export',  [\App\Http\Controllers\Web\Admin\KardexController::class, 'export'])->name('export');
});

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

// ─────────────────────────────────────────────────────────────────
// BRECHAS IMPLEMENTADAS — Nuevas rutas del sistema Dropana Bolivia
// ─────────────────────────────────────────────────────────────────

// [BRECHA #4] Panel Fulfillment (Tiendas Online que tercerizan logística a Dropana)
Route::get('/panel/fulfillment', [PanelController::class, 'fulfillment'])->name('panel.fulfillment');

// [BRECHA #5] Cobro QR/Efectivo en Campo para el Repartidor
Route::get('/carrier/cobro', [PanelController::class, 'carrierCobro'])->name('carrier.cobro');
Route::get('/carrier/cobro/{guide}', [PanelController::class, 'carrierCobro'])->name('carrier.cobro.guide');

// [BRECHA #7] Tracking público de pedido (sin necesidad de login)
Route::get('/tracking', function () {
    return view('tracking');
})->name('tracking.index');

Route::get('/tracking/{guide}', function (string $guide) {
    // En producción esto cargará datos reales del modelo Order
    return view('tracking', ['prefillGuide' => $guide]);
})->name('tracking.show');

// [BRECHA #8] Páginas públicas de Soporte y Contáctenos
Route::get('/soporte', function () {
    return view('soporte');
})->name('soporte');

Route::get('/contactenos', function () {
    return view('contactenos');
})->name('contactenos');

