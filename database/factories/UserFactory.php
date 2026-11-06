<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Shared hashed password across factory calls for performance */
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'tenant_id'            => Tenant::factory(),
            'name'                 => fake()->name(),
            'email'                => fake()->unique()->safeEmail(),
            'role'                 => 'dropshipper',
            'phone'                => '+591 ' . fake()->numerify('########'),
            'document_id'          => fake()->numerify('#######'),
            'company_or_store_name'=> fake()->company(),
            'city'                 => fake()->randomElement(['Santa Cruz', 'La Paz', 'Cochabamba', 'Sucre', 'Oruro']),
            'department'           => fake()->randomElement(['Santa Cruz', 'La Paz', 'Cochabamba']),
            'is_active'            => true,
            'email_verified_at'    => now(),
            'password'             => static::$password ??= Hash::make('password'),
            'remember_token'       => Str::random(10),
        ];
    }

    /** Marca el email como no verificado */
    public function unverified(): static
    {
        return $this->state(fn() => ['email_verified_at' => null]);
    }

    /** Admin de tenant */
    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    /** Proveedor */
    public function provider(): static
    {
        return $this->state(fn() => ['role' => 'provider']);
    }

    /** Carrier Agent */
    public function carrier(): static
    {
        return $this->state(fn() => ['role' => 'carrier_agent']);
    }
}
