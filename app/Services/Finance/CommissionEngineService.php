<?php

namespace App\Services\Finance;

use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\OrderItem;

class CommissionEngineService
{
    /**
     * Calcula la comisión de la plataforma para un pedido en base a la jerarquía de reglas:
     * 1. Regla específica por Proveedor
     * 2. Regla por Categoría de producto
     * 3. Regla por Nivel del Dropshipper
     * 4. Regla Global del Tenant
     */
    public function calculatePlatformFee(Order $order): float
    {
        $tenantId = $order->tenant_id;
        $totalAmount = (float) $order->total_amount_bob;

        // Buscar reglas activas ordenadas por prioridad (ascendente)
        $rules = CommissionRule::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $order)) {
                return $this->computeFee($rule, $totalAmount);
            }
        }

        // Tasa por defecto de contingencia: 5% + 2 BOB
        return round(($totalAmount * 0.05) + 2.00, 2);
    }

    protected function matchesRule(CommissionRule $rule, Order $order): bool
    {
        return match ($rule->rule_scope) {
            'global' => true,
            'provider' => $order->items->contains(fn(OrderItem $i) => $i->provider_id === $rule->scope_reference_id),
            'category' => $order->items->contains(fn(OrderItem $i) => $i->product->category_id === $rule->scope_reference_id),
            'carrier' => $order->carrier_id === $rule->scope_reference_id,
            default => false,
        };
    }

    protected function computeFee(CommissionRule $rule, float $amount): float
    {
        $fee = 0.0;

        if ($rule->commission_type === 'percentage' && $rule->percentage_rate) {
            $fee = $amount * (float) $rule->percentage_rate;
        } elseif ($rule->commission_type === 'fixed_amount' && $rule->fixed_fee_bob) {
            $fee = (float) $rule->fixed_fee_bob;
        } elseif ($rule->commission_type === 'hybrid') {
            $fee = ($amount * (float) ($rule->percentage_rate ?? 0)) + (float) ($rule->fixed_fee_bob ?? 0);
        }

        // Aplicar límites mínimos y máximos (Cap / Floor)
        if ($rule->min_fee_bob && $fee < (float) $rule->min_fee_bob) {
            $fee = (float) $rule->min_fee_bob;
        }
        if ($rule->max_fee_bob && $fee > (float) $rule->max_fee_bob) {
            $fee = (float) $rule->max_fee_bob;
        }

        return round($fee, 2);
    }
}
