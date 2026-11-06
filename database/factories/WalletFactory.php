<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'tenant_id'         => Tenant::factory(),
            'user_id'           => User::factory(),
            'currency'          => 'BOB',
            'balance_available' => fake()->randomFloat(2, 0, 5000),
            'balance_pending'   => fake()->randomFloat(2, 0, 500),
            'balance_withdrawn' => fake()->randomFloat(2, 0, 2000),
        ];
    }
}
