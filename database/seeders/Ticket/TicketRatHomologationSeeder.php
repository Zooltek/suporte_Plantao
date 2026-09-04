<?php

namespace Database\Seeders\Ticket;

use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\CustomerGroup;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module as RatModule;
use App\Models\Software;
use App\Models\State;
use Illuminate\Database\Seeder;

class TicketRatHomologationSeeder extends Seeder
{
    public function run(): void
    {
        $software = Software::query()->firstOrCreate(
            ['name' => 'EasyMaster'],
            ['version' => '01.32.01', 'status' => true]
        );

        $state = State::query()->firstOrCreate(
            ['abbreviation' => 'SP'],
            ['name' => 'Sao Paulo']
        );

        $group = CustomerGroup::query()->firstOrCreate(
            ['hash' => 'hml-rat'],
            ['name' => 'Homologacao RAT', 'status' => true]
        );

        $financeiroRat = $this->ensureRatModule(
            name: 'Financeiro',
            project: 'EasyMaster',
            slug: 'financeiro'
        );

        $cadastroRat = $this->ensureRatModule(
            name: 'Cadastro',
            project: 'EasyMaster',
            slug: 'cadastro'
        );

        $estoqueRat = $this->ensureRatModule(
            name: 'Estoque',
            project: 'EasyMaster',
            slug: 'estoque'
        );

        $vendasRat = $this->ensureRatModule(
            name: 'Vendas RC',
            project: 'EasyMaster',
            slug: 'vendas_rc'
        );

        $autoFinanceiro = CompanyModuleType::query()->updateOrCreate(
            ['slug' => 'homologacao_financeiro'],
            [
                'name' => 'Homologacao Financeiro',
                'is_active' => true,
                'sort_order' => 90,
                'rat_module_id' => $financeiroRat->id,
            ]
        );

        $autoCadastro = CompanyModuleType::query()->updateOrCreate(
            ['slug' => 'homologacao_cadastro'],
            [
                'name' => 'Homologacao Cadastro',
                'is_active' => true,
                'sort_order' => 91,
                'rat_module_id' => $cadastroRat->id,
            ]
        );

        $manualAplicativo = CompanyModuleType::query()->firstOrCreate(
            ['slug' => 'aplicativo'],
            [
                'name' => 'Aplicativo',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $manualEcommerce = CompanyModuleType::query()->firstOrCreate(
            ['slug' => 'ecommerce'],
            [
                'name' => 'E-commerce',
                'is_active' => true,
                'sort_order' => 6,
            ]
        );

        $companies = [
            [
                'cnpj' => '90.000.000/0001-01',
                'name' => 'Cliente Homologacao RAT Automatico LTDA',
                'trade_name' => 'HML RAT Automatico',
                'contact_name' => 'Contato HML Automatico',
                'contact_email' => 'hml.rat.automatico@example.com',
                'phone' => '(11) 4000-0001',
                'telephone_2' => '(11) 94000-0001',
                'observations' => 'Homologacao visual do fluxo RAT com template padrao automatico.',
                'module_ids' => [$autoFinanceiro->id],
                'schedule_module_ids' => [$financeiroRat->id],
            ],
            [
                'cnpj' => '90.000.000/0001-02',
                'name' => 'Cliente Homologacao RAT Manual LTDA',
                'trade_name' => 'HML RAT Manual',
                'contact_name' => 'Contato HML Manual',
                'contact_email' => 'hml.rat.manual@example.com',
                'phone' => '(11) 4000-0002',
                'telephone_2' => '(11) 94000-0002',
                'observations' => 'Homologacao visual do fluxo RAT com selecao manual de checklist tecnico.',
                'module_ids' => [$manualAplicativo->id],
                'schedule_module_ids' => [$cadastroRat->id, $estoqueRat->id],
            ],
            [
                'cnpj' => '90.000.000/0001-03',
                'name' => 'Cliente Homologacao RAT Misto LTDA',
                'trade_name' => 'HML RAT Misto',
                'contact_name' => 'Contato HML Misto',
                'contact_email' => 'hml.rat.misto@example.com',
                'phone' => '(11) 4000-0003',
                'telephone_2' => '(11) 94000-0003',
                'observations' => 'Homologacao visual do fluxo RAT com modulo automatico e modulo legado sem template padrao.',
                'module_ids' => [$manualEcommerce->id, $autoCadastro->id],
                'schedule_module_ids' => [$vendasRat->id, $cadastroRat->id, $financeiroRat->id],
            ],
        ];

        foreach ($companies as $payload) {
            $company = Company::query()->updateOrCreate(
                ['cnpj' => $payload['cnpj']],
                [
                    'name' => $payload['name'],
                    'trade_name' => $payload['trade_name'],
                    'customer_group_id' => $group->id,
                    'state_id' => $state->id,
                    'software_id' => $software->id,
                    'contact_name' => $payload['contact_name'],
                    'contact_email' => $payload['contact_email'],
                    'phone' => $payload['phone'],
                    'telephone_2' => $payload['telephone_2'],
                    'address' => 'Rua de Homologacao, 100',
                    'city' => 'Sao Paulo',
                    'bairro' => 'Centro',
                    'observations' => $payload['observations'],
                    'has_ecommerce' => true,
                    'has_crm' => true,
                    'has_tef' => true,
                    'is_active' => true,
                    'financial_irregular' => false,
                ]
            );

            $company->moduleTypes()->sync($payload['module_ids']);
            $company->scheduleModules()->sync($payload['schedule_module_ids']);
        }

        $this->command?->info('TicketRatHomologationSeeder: base de homologacao RAT atualizada com sucesso.');
    }

    private function ensureRatModule(string $name, string $project, string $slug): RatModule
    {
        $module = RatModule::query()->firstOrCreate(
            ['name' => $name],
            ['project' => $project]
        );

        $group = ElementGroup::query()->firstOrCreate(['name' => 'Homologacao']);

        ElementType::query()->firstOrCreate(
            ['name' => "hml_{$slug}_check"],
            [
                'label' => "Checklist {$name}",
                'type' => 'checkbox',
                'module_id' => $module->id,
                'group_id' => $group->id,
            ]
        );

        ElementType::query()->firstOrCreate(
            ['name' => "hml_{$slug}_obs"],
            [
                'label' => "Observacao {$name}",
                'type' => 'text',
                'module_id' => $module->id,
                'group_id' => $group->id,
            ]
        );

        return $module;
    }
}
