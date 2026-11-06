<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 800);

        return [
            'tenant_id'                => Tenant::factory(),
            'wallet_id'                => Wallet::factory(),
            'transaction_type'         => fake()->randomElement(['order_profit', 'withdrawal', 'manual_credit']),
            'amount'                   => $amount,
            'balance_after'            => fake()->randomFloat(2, 0, 3000),
            'reference_type'           => 'order',
            'reference_id'             => null,
            'description'              => fake()->sentence(5),
            'metadata'                 => null,
            'notes'                    => null,
            'bank_settlement_batch_id' => null,
        ];
    }

    /** Estado: retiro pendiente sin lote */
    public function pendingWithdrawal(float $amount = 200.00): static
    {
        return $this->state(fn() => [
            'transaction_type'         => 'withdrawal',
            'amount'                   => -abs($amount),
            'bank_settlement_batch_id' => null,
        ]);
    }
}
