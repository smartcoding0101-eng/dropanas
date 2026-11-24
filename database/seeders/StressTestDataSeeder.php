<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class StressTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Genera 500 pedidos, proveedores, couriers y liquidaciones de estrés.
     */
    public function run(): void
    {
        $this->command->info('Iniciando generación de 500 pedidos de prueba y ecosistema Bolivia COD...');
        Artisan::call('system:stress-test', ['--orders' => 500]);
        $this->command->info(Artisan::output());
    }
}
