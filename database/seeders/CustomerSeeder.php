<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SoftwareSeeder::class);

        $softwareIds = Software::query()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        Customer::factory()
            ->count(20)
            ->state(new Sequence(
                fn (Sequence $sequence): array => [
                    'software_id' => $softwareIds[$sequence->index % count($softwareIds)],
                ],
            ))
            ->create();

        Customer::updateOrCreate(
            ['cnpj' => '12.345.678/0001-99'],
            [
                'name' => 'Cliente Exemplo Ltda',
                'trade_name' => 'Exemplo',
                'contact_name' => 'João da Silva',
                'contact_email' => 'joao@exemple.com',
                'phone' => '(11) 99999-9999',
                'telephone_2' => '(11) 88888-8888',
                'whatsapp_phone' => '5527988213355',
                'city' => 'São Paulo',
                'bairro' => 'Centro',
                'customer_group_id' => 1,
                'state_id' => 1,
                'software_id' => $softwareIds[0],
                'is_active' => true,
                'financial_irregular' => false,
                'created_at' => now(),
            ]
        );

        $this->command->info('Clientes criados com sucesso!');
    }
}
