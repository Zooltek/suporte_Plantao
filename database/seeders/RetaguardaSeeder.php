<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetaguardaSeeder extends Seeder
{
    private const CAPITALS_BY_STATE = [
        'AC' => 'Rio Branco',
        'AL' => 'Maceió',
        'AP' => 'Macapá',
        'AM' => 'Manaus',
        'BA' => 'Salvador',
        'CE' => 'Fortaleza',
        'DF' => 'Brasília',
        'ES' => 'Vitória',
        'GO' => 'Goiânia',
        'MA' => 'São Luís',
        'MT' => 'Cuiabá',
        'MS' => 'Campo Grande',
        'MG' => 'Belo Horizonte',
        'PA' => 'Belém',
        'PB' => 'João Pessoa',
        'PR' => 'Curitiba',
        'PE' => 'Recife',
        'PI' => 'Teresina',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Natal',
        'RO' => 'Porto Velho',
        'RR' => 'Boa Vista',
        'RS' => 'Porto Alegre',
        'SC' => 'Florianópolis',
        'SE' => 'Aracaju',
        'SP' => 'São Paulo',
        'TO' => 'Palmas',
    ];

    public function run(): void
    {
        $this->seedCities();
        $this->seedBillingCatalog();
        $this->seedSampleInvoices();
    }

    private function seedCities(): void
    {
        $now = now();

        foreach (State::query()->orderBy('abbreviation')->get() as $state) {
            DB::table('cities')->updateOrInsert(
                [
                    'state_id' => $state->id,
                    'name' => self::CAPITALS_BY_STATE[$state->abbreviation] ?? $state->name,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedBillingCatalog(): void
    {
        $statuses = [
            ['name' => 'Em aberto', 'description' => 'Cobrança pendente de pagamento.'],
            ['name' => 'Pago', 'description' => 'Cobrança quitada pelo cliente.'],
            ['name' => 'Vencido', 'description' => 'Cobrança vencida sem baixa financeira.'],
        ];

        foreach ($statuses as $status) {
            DB::table('sales_order_status')->updateOrInsert(
                ['name' => $status['name']],
                ['description' => $status['description']]
            );
        }

        $services = [
            ['name' => 'Mensalidade Suporte', 'description' => 'Plano mensal de suporte técnico.'],
            ['name' => 'Implantação', 'description' => 'Serviço de implantação assistida.'],
            ['name' => 'Consultoria', 'description' => 'Horas de consultoria especializada.'],
        ];

        foreach ($services as $service) {
            DB::table('service')->updateOrInsert(
                ['name' => $service['name']],
                ['description' => $service['description']]
            );
        }
    }

    private function seedSampleInvoices(): void
    {
        $customerIds = Customer::query()->orderBy('id')->limit(3)->pluck('id');
        $serviceIds = DB::table('service')->orderBy('id')->pluck('id')->all();
        $statusIds = DB::table('sales_order_status')->orderBy('id')->pluck('id')->all();
        $amounts = [149.90, 289.50, 420.00];

        if ($customerIds->isEmpty() || $serviceIds === [] || $statusIds === []) {
            return;
        }

        foreach ($customerIds->values() as $index => $customerId) {
            $dueDate = now()->copy()->startOfMonth()->addMonths($index)->setDay(10 + $index)->startOfDay();

            DB::table('sales_order')->updateOrInsert(
                [
                    'customer_id' => $customerId,
                    'service_id' => $serviceIds[$index % count($serviceIds)],
                    'due_date' => $dueDate->toDateTimeString(),
                ],
                [
                    'status_id' => $statusIds[$index % count($statusIds)],
                    'amount' => $amounts[$index % count($amounts)],
                ]
            );
        }
    }
}
