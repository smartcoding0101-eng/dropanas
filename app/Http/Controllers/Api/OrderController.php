<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Logistics\CarrierAdapterInterface;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\CarrierCoverage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Finance\CommissionEngineService;
use App\Services\Logistics\Adapters\DexBoliviaAdapter;
use App\Services\Payments\PaymentManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Listado de pedidos del usuario autenticado (con filtros por estado y rol).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Order::with(['items.product', 'warehouse', 'carrier']);

        if ($user->isDropshipper()) {
            $query->where('dropshipper_id', $user->id);
        } elseif ($user->isProvider()) {
            $query->whereHas('items', function ($q) use ($user) {
                $q->where('provider_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Creación de un pedido COD (Contra Entrega) en Bolivia.
     */
    public function store(
        Request $request,
        CommissionEngineService $commissionEngine,
        PaymentManagerService $paymentManager,
        DexBoliviaAdapter $dexAdapter
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'carrier_code' => 'required|string|exists:carriers,code',
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:30', // Teléfono boliviano
            'customer_document_id' => 'nullable|string|max:30', // CI
            'shipping_department' => 'required|string|max:50',
            'shipping_city' => 'required|string|max:100',
            'shipping_address' => 'required|string|max:255',
            'shipping_reference' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery_bob,qr_interoperable,binance_pay,takenos',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price_bob' => 'required|numeric|min:1', // Precio final fijado por el dropshipper
        ]);

        $carrier = Carrier::where('code', $validated['carrier_code'])->firstOrFail();

        // Obtener tarifa de flete para la ciudad de destino
        $coverage = CarrierCoverage::where('carrier_id', $carrier->id)
            ->where('department', $validated['shipping_department'])
            ->first();

        $shippingFee = $coverage ? (float) $coverage->base_shipping_bob : 25.00; // Tarifa base por defecto

        return DB::transaction(function () use ($validated, $user, $carrier, $shippingFee, $commissionEngine, $paymentManager, $dexAdapter) {
            $totalAmount = 0.0;
            $costAmount = 0.0;
            $itemsData = [];

            foreach ($validated['items'] as $itemInput) {
                $product = Product::findOrFail($itemInput['product_id']);
                $qty = $itemInput['quantity'];
                $sellingPrice = (float) $itemInput['selling_price_bob'];
                $unitCost = (float) $product->cost_price_bob;

                if ($sellingPrice < (float) $product->min_price_bob) {
                    abort(422, "El precio de venta para '{$product->name}' no puede ser menor a {$product->min_price_bob} BOB.");
                }

                // Auditoría de inventario: verificar disponibilidad y reservar en bodega
                $inventory = \App\Models\WarehouseInventory::where('warehouse_id', $validated['warehouse_id'])
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    if ($inventory->stock_available < $qty) {
                        abort(422, "Stock insuficiente en la bodega para '{$product->name}'. Disponible: {$inventory->stock_available}, solicitado: {$qty}.");
                    }
                    $inventory->stock_available -= $qty;
                    $inventory->stock_reserved += $qty;
                    $inventory->save();
                }

                $subtotal = $sellingPrice * $qty;
                $totalAmount += $subtotal;
                $costAmount += ($unitCost * $qty);

                $itemsData[] = [
                    'product_id' => $product->id,
                    'provider_id' => $product->provider_id,
                    'quantity' => $qty,
                    'unit_cost_bob' => $unitCost,
                    'unit_price_bob' => $sellingPrice,
                    'subtotal_bob' => $subtotal,
                ];
            }

            $orderNumber = 'BO-' . date('ymd') . '-' . strtoupper(Str::random(5));

            $order = Order::create([
                'tenant_id' => $user->tenant_id,
                'dropshipper_id' => $user->id,
                'warehouse_id' => $validated['warehouse_id'],
                'carrier_id' => $carrier->id,
                'order_number' => $orderNumber,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_document_id' => $validated['customer_document_id'] ?? null,
                'shipping_department' => $validated['shipping_department'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_reference' => $validated['shipping_reference'] ?? null,
                'total_amount_bob' => $totalAmount,
                'cost_amount_bob' => $costAmount,
                'shipping_fee_bob' => $shippingFee,
                'platform_fee_bob' => 0.00,
                'dropshipper_profit_bob' => 0.00,
                'status' => 'pending_confirmation',
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create(array_merge($item, ['tenant_id' => $user->tenant_id]));
            }

            // Trazabilidad Kardex Global: Registrar reserva de inventario
            $kardexService = app(\App\Services\Inventory\KardexService::class);
            foreach ($order->items as $orderItem) {
                $inv = \App\Models\WarehouseInventory::where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $orderItem->product_id)
                    ->first();
                if ($inv && $orderItem->product) {
                    $kardexService->recordOrderReservation(
                        $inv,
                        $orderItem->product,
                        (int) $orderItem->quantity,
                        $order->order_number,
                        $order->id,
                        $user->id
                    );
                }
            }

            // Calcular comisiones variables
            $order->load('items.product');
            $platformFee = $commissionEngine->calculatePlatformFee($order);
            $order->platform_fee_bob = $platformFee;

            // Ganancia Dropshipper = Total - Costo - Flete - Comisión Plataforma
            $order->dropshipper_profit_bob = max(0, $totalAmount - $costAmount - $shippingFee - $platformFee);
            $order->save();

            // Generar Guía Logística
            $waybill = $dexAdapter->generateWaybill($order);

            // Generar Carga Útil de Pago si no es estrictamente efectivo puro
            $paymentPayload = null;
            if (in_array($validated['payment_method'], ['qr_interoperable', 'binance_pay', 'takenos'])) {
                $paymentPayload = $paymentManager->initiatePayment($order, $validated['payment_method']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pedido contra entrega registrado exitosamente.',
                'data' => [
                    'order' => $order->load(['items.product', 'carrier', 'warehouse']),
                    'waybill' => $waybill,
                    'payment' => $paymentPayload,
                ],
            ], 201);
        });
    }

    /**
     * Consulta del estado del pedido y seguimiento.
     */
    public function show(string $id): JsonResponse
    {
        $order = Order::with(['items.product', 'carrier', 'warehouse'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $order,
        ]);
    }
}
