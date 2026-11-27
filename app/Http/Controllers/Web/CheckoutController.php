<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * Vista del Demo de Checkout COD + QR para Bolivia.
     */
    public function index(Request $request)
    {
        // Garantizar saldo suficiente para pruebas operativas en el demo
        $demoDropshipper = User::where('email', 'dropshipper@bolidrop.bo')->first();
        if ($demoDropshipper) {
            $wallet = Wallet::firstOrCreate(
                ['tenant_id' => $demoDropshipper->tenant_id, 'user_id' => $demoDropshipper->id],
                ['currency' => 'BOB', 'balance_available' => 10000.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
            );
            if ((float) $wallet->balance_available < 3000.00) {
                $wallet->balance_available = 10000.00;
                $wallet->save();
            }
        }

        $authUser = auth()->user();
        if ($authUser) {
            $userWallet = Wallet::firstOrCreate(
                ['tenant_id' => $authUser->tenant_id, 'user_id' => $authUser->id],
                ['currency' => 'BOB', 'balance_available' => 10000.00, 'balance_pending' => 0.00, 'balance_withdrawn' => 0.00]
            );
            if ((float) $userWallet->balance_available < 3000.00) {
                $userWallet->balance_available = 10000.00;
                $userWallet->save();
            }
        }

        $currentApiToken = $authUser ? $authUser->createToken('checkout_web')->plainTextToken : null;

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

        return view('checkout', compact('products', 'productsJson', 'warehouses', 'carriers', 'selectedProduct', 'currentApiToken'));
    }
}

