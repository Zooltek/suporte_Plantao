<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contract;
use Carbon\Carbon;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        Contract::updateOrCreate(
            ['contract_number' => 'CT-0001'],
            [
                'customer_id' => 1,
                'start_date'  => Carbon::parse('2024-01-01'),
                'end_date'    => Carbon::parse('2025-01-01'),
                'value'       => 15000,
            ]
        );

        $this->command->info('Contrato criado com sucesso!');
    }
}
