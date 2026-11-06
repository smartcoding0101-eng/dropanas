<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'code'          => strtoupper(Str::random(6)),
            'company_name'  => fake()->company(),
            'legal_nit'     => fake()->numerify('#########'),
            'country_code'  => 'BO',
            'currency_code' => 'BOB',
            'domain'        => fake()->unique()->domainName(),
            'support_email' => fake()->companyEmail(),
            'support_phone' => '+591 ' . fake()->numerify('########'),
            'is_active'     => true,
            'settings'      => [],
        ];
    }
}
