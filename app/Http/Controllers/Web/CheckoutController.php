<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Vista del Demo de Checkout COD + QR para Bolivia.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'provider', 'warehouseInventories.warehouse'])
            ->where('status', 'published')
            ->where('is_cod_available', true);

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        $products = $query->get();

        $productsJson = $products->map(function ($p) {
            $providerName = $p->provider?->company_or_store_name ?: ($p->provider?->name ?: 'Proveedor Mayorista');
            $pWarehouses = $p->warehouseInventories->filter(fn($wi) => $wi->stock_available > 0)->map(fn($wi) => [
                'id' => (string) $wi->warehouse_id,
                'name' => $wi->warehouse?->name,
                'city' => $wi->warehouse?->city,
                'stock' => (int) $wi->stock_available,
            ])->values();

            return [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
                'cost' => (float) $p->cost_price_bob,
                'suggested' => (float) $p->suggested_price_bob,
                'min_price' => (float) $p->min_price_bob,
                'provider' => $providerName,
                'stock' => (int) $p->warehouseInventories->sum('stock_available'),
                'warehouses' => $pWarehouses,
            ];
        })->values();

        $warehouses = Warehouse::where('is_active', true)->get();
        $carriers   = Carrier::where('has_cod_service', true)->where('is_active', true)->get();

        $selectedProduct = null;
        if ($request->filled('product_id')) {
            $selectedProduct = Product::with(['category', 'provider', 'warehouseInventories.warehouse'])->find($request->product_id);
        }

        return view('checkout', compact('products', 'productsJson', 'warehouses', 'carriers', 'selectedProduct'));
    }
}

