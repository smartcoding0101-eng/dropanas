<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class StressTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 500 pedidos, proveedores, couriers y liquidaciones de estrés de forma segura e idempotente.
     */
    public function run(): void
    {
        $stressOrdersCount = Order::where('order_number', 'LIKE', 'STRESS-%')->count();
        if ($stressOrdersCount >= 500) {
            $this->command->info("ℹ️ Ya existen {$stressOrdersCount} pedidos de prueba en la base de datos.");
            $this->command->info("🎯 Los datos de prueba ya están completamente cargados y disponibles.");
            return;
        }

        $this->command->info('Iniciando generación de 500 pedidos de prueba y ecosistema Bolivia COD...');
        Artisan::call('system:stress-test', ['--orders' => 500]);
        $this->command->info(Artisan::output());
    }
}
