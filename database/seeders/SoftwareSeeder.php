<?php

namespace Database\Seeders;

use App\Models\Software;
use Illuminate\Database\Seeder;

/**
 * Popula os produtos reais da empresa na tabela softwares.
 * Idempotente via firstOrCreate.
 */
class SoftwareSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'EasyMaster',  'version' => '01.32.01', 'status' => 1],
            ['name' => 'EasyControl', 'version' => '02.10.00', 'status' => 1],
            ['name' => 'AmuraWeb',    'version' => '03.05.00', 'status' => 1],
            ['name' => 'Plenus',      'version' => '01.00.00', 'status' => 1],
        ];

        foreach ($products as $product) {
            Software::firstOrCreate(
                ['name' => $product['name']],
                ['version' => $product['version'], 'status' => $product['status']]
            );
        }

        $this->command->info('SoftwareSeeder: 4 produtos cadastrados (EasyMaster, EasyControl, AmuraWeb, Plenus).');
    }
}
