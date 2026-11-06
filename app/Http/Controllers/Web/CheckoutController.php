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
        $products = Product::with(['category', 'warehouseInventories.warehouse'])
            ->where('status', 'published')
            ->where('is_cod_available', true)
            ->get();

        $warehouses = Warehouse::where('is_active', true)->get();
        $carriers   = Carrier::where('has_cod_service', true)->get();

        $selectedProduct = null;
        if ($request->filled('product_id')) {
            $selectedProduct = Product::find($request->product_id);
        }

        return view('checkout', compact('products', 'warehouses', 'carriers', 'selectedProduct'));
    }
}
