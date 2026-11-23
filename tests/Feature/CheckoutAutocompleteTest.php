<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BoliviaEcosystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(BoliviaEcosystemSeeder::class);
    }

    public function test_checkout_demo_has_product_search_and_price_autocomplete(): void
    {
        $user = User::where('role', 'dropshipper')->first();
        $response = $this->actingAs($user)->get('/checkout-demo');

        $response->assertStatus(200);
        $response->assertSee('productSearchInput');
        $response->assertSee('price-suggestions-list');
        $response->assertSee('setPrice');
        $response->assertSee('Autocompletado en tiempo real', false);
        $response->assertSee('Buscar Producto por Nombre, SKU o Precio');
        $response->assertSee('filteredProducts');
    }
}
