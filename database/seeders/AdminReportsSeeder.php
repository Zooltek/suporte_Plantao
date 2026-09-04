<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Software;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Popula dados para os 3 relatórios admin:
 *  1. Tarefas por Módulo    — via TaskSeeder (já existente)
 *  2. Resumo por Problema   — Tickets com sub_category_id preenchido
 *  3. Implantação           — Companies com tickets abertos + schedule_record ativo
 */
class AdminReportsSeeder extends Seeder
{
    private const DAILY_PROBLEMS_MARKER = '[seed:admin-reports:daily-problems]';

    private const IMPLEMENTATION_MARKER = '[seed:admin-reports:implementation]';

    // Problemas / subcategorias para o relatório "Resumo por Problema"
    private array $problems = [
        'Lentidão no sistema',
        'Erro ao emitir NF-e',
        'Falha na importação de XML',
        'Divergência no estoque',
        'Acesso negado ao módulo',
        'Erro de cálculo no financeiro',
        'Impressão de boleto com falha',
        'Integração com e-commerce offline',
        'Sincronização de caixa',
        'Quebra de layout no relatório',
    ];

    public function run(): void
    {
        $this->command->info('🌱  Iniciando AdminReportsSeeder...');

        DB::transaction(function () {
            // ---------- Dependências base ----------
            $this->ensureOrigins();
            $statuses = $this->ensureStatuses();
            $priorities = $this->ensurePriorities();
            $agents = $this->ensureAgents();
            $softwares = $this->ensureSoftwares();
            $companies = $this->ensureCompanies($softwares);

            // Categorias pai + subcategorias (problemas)
            $subCategories = $this->ensureCategories();

            // ---------- RELATÓRIO 2: Resumo por Problema ----------
            $this->command->info('  ↳ Gerando tickets para Resumo por Problema...');
            $this->seedDailyProblemsTickets($companies, $agents, $subCategories, $statuses, $priorities);

            // ---------- RELATÓRIO 3: Clientes em Implantação ----------
            $this->command->info('  ↳ Gerando dados de Implantação...');
            $this->seedImplementationData($companies, $agents);
        });

        $this->command->info('✅  AdminReportsSeeder concluído!');
        $this->command->info('   Relatório 1 (Tarefas por Módulo): rode php artisan db:seed --class=TaskSeeder');
        $this->command->info('   Relatório 2 (Resumo por Problema): /admin/reports/daily-problems');
        $this->command->info('   Relatório 3 (Implantação):         /admin/reports/implementation-clients');
    }

    // ───────────────────────────────────────────────────────────────
    //  RELATÓRIO 2 — Tickets com sub_category_id
    // ───────────────────────────────────────────────────────────────
    private function seedDailyProblemsTickets(
        \Illuminate\Support\Collection $companies,
        \Illuminate\Support\Collection $agents,
        \Illuminate\Support\Collection $subCategories,
        \Illuminate\Support\Collection $statuses,
        \Illuminate\Support\Collection $priorities
    ): void {
        if ($this->hasDailyProblemsSeedData()) {
            $this->command->info('    • Dados de Resumo por Problema já existentes, seed ignorado.');

            return;
        }

        $today = now();
        $openIds = $statuses->whereNotIn('id', [3])->pluck('id')->all();
        $closedId = $statuses->firstWhere('id', 3)?->id ?? 3;
        $companyIndex = 0;
        $nextCompanyId = function () use ($companies, &$companyIndex): int {
            $companyId = $companies[$companyIndex % $companies->count()]->id;
            $companyIndex++;

            return $companyId;
        };

        foreach ($subCategories as $subCat) {
            $parentId = $subCat->parent_id;

            // 2-5 tickets pendentes acumulados (criados nos últimos 30 dias)
            $pendingCount = rand(2, 5);
            for ($i = 0; $i < $pendingCount; $i++) {
                Ticket::factory()->create([
                    'subject' => fake()->sentence(5),
                    'content' => fake()->paragraph(2),
                    'author_id' => User::inRandomOrder()->value('id'),
                    'user_id' => User::inRandomOrder()->value('id'),
                    'agent_id' => $agents->random()->id,
                    'status_id' => $openIds[array_rand($openIds)],
                    'priority_id' => $priorities->random()->id,
                    'category_id' => $parentId,
                    'sub_category_id' => $subCat->category_id,
                    'company_id' => $nextCompanyId(),
                    'contact' => fake()->name(),
                    'obs' => self::DAILY_PROBLEMS_MARKER,
                    'completed_at' => null,
                    'created_at' => now()->subDays(rand(3, 30)),
                    'updated_at' => now()->subDays(rand(0, 2)),
                ]);
            }

            // 1-3 tickets abertos hoje
            $todayCount = rand(1, 3);
            for ($i = 0; $i < $todayCount; $i++) {
                Ticket::factory()->create([
                    'subject' => fake()->sentence(5),
                    'content' => fake()->paragraph(2),
                    'author_id' => User::inRandomOrder()->value('id'),
                    'user_id' => User::inRandomOrder()->value('id'),
                    'agent_id' => $agents->random()->id,
                    'status_id' => $openIds[array_rand($openIds)],
                    'priority_id' => $priorities->random()->id,
                    'category_id' => $parentId,
                    'sub_category_id' => $subCat->category_id,
                    'company_id' => $nextCompanyId(),
                    'contact' => fake()->name(),
                    'obs' => self::DAILY_PROBLEMS_MARKER,
                    'completed_at' => null,
                    'created_at' => $today->copy()->subHours(rand(1, 10)),
                    'updated_at' => $today->copy(),
                ]);
            }

            // 1-4 tickets finalizados hoje
            $resolvedCount = rand(1, 4);
            for ($i = 0; $i < $resolvedCount; $i++) {
                $createdAt = now()->subHours(rand(3, 12));
                Ticket::factory()->create([
                    'subject' => fake()->sentence(5),
                    'content' => fake()->paragraph(2),
                    'author_id' => User::inRandomOrder()->value('id'),
                    'user_id' => User::inRandomOrder()->value('id'),
                    'agent_id' => $agents->random()->id,
                    'status_id' => $closedId,
                    'priority_id' => $priorities->random()->id,
                    'category_id' => $parentId,
                    'sub_category_id' => $subCat->category_id,
                    'company_id' => $nextCompanyId(),
                    'contact' => fake()->name(),
                    'obs' => self::DAILY_PROBLEMS_MARKER,
                    'completed_at' => $today->copy()->subHours(rand(1, 5)),
                    'created_at' => $createdAt,
                    'updated_at' => $today->copy(),
                ]);
            }
        }
    }

    // ───────────────────────────────────────────────────────────────
    //  RELATÓRIO 3 — Clientes em Implantação
    // ───────────────────────────────────────────────────────────────
    private function seedImplementationData(
        \Illuminate\Support\Collection $companies,
        \Illuminate\Support\Collection $agents
    ): void {
        if ($this->hasImplementationSeedData()) {
            $this->command->info('    • Dados de Implantação já existentes, seed ignorado.');

            return;
        }

        // Seleciona metade das empresas para implantação
        $implantacao = $companies->random((int) ceil($companies->count() / 2));

        // Qualquer categoria válida para não violar NOT NULL
        $defaultCategoryId = \DB::table('solutions_category')->value('category_id') ?? 1;

        foreach ($implantacao as $company) {
            // 1-4 tickets abertos para este cliente
            $ticketCount = rand(1, 4);
            for ($i = 0; $i < $ticketCount; $i++) {
                Ticket::factory()->create([
                    'subject' => fake()->sentence(5),
                    'content' => fake()->paragraph(2),
                    'author_id' => User::inRandomOrder()->value('id'),
                    'user_id' => User::inRandomOrder()->value('id'),
                    'agent_id' => $agents->random()->id,
                    'status_id' => collect([1, 2, 4])->random(),
                    'priority_id' => 1,
                    'category_id' => $defaultCategoryId,
                    'company_id' => $company->id,
                    'contact' => $company->contact_name ?? fake()->name(),
                    'obs' => self::IMPLEMENTATION_MARKER,
                    'completed_at' => null,
                    'created_at' => now()->subDays(rand(1, 20)),
                    'updated_at' => now()->subDays(rand(0, 1)),
                ]);
            }

            // 1-2 registros ativos em schedule_record (status = 1)
            $scheduleCount = rand(1, 2);
            $agent = $agents->random();
            $start = now()->subDays(rand(2, 10));

            for ($i = 0; $i < $scheduleCount; $i++) {
                // Precisamos de um schedule pai
                $scheduleId = DB::table('schedule')->insertGetId([
                    'customer_id' => $company->id,
                    'agent_id' => $agent->id,
                    'module_id' => 1,
                    'start_at' => $start,
                    'contact' => $company->contact_name ?? fake()->name(),
                    'obs' => self::IMPLEMENTATION_MARKER.' '.fake()->sentence(),
                    'status' => 'sch',
                    'created_at' => $start,
                    'updated_at' => $start,
                ]);

                DB::table('schedule_record')->insert([
                    'schedule_id' => $scheduleId,
                    'module_id' => 1,
                    'agent_id' => $agent->id,
                    'customer_id' => $company->id,
                    'contact' => $company->contact_name ?? fake()->name(),
                    'obs' => self::IMPLEMENTATION_MARKER.' '.fake()->sentence(),
                    'start' => $start,
                    'end' => $start->copy()->addHours(8),
                    'status' => 1,
                    'created_at' => $start,
                    'updated_at' => $start,
                ]);
            }
        }
    }

    // ───────────────────────────────────────────────────────────────
    //  HELPERS — Dependências Base
    // ───────────────────────────────────────────────────────────────
    private function ensureOrigins(): void
    {
        DB::table('ticketit_origin')->insertOrIgnore([
            ['id' => 1, 'name' => 'Sistema Interno'],
            ['id' => 2, 'name' => 'Portal do Cliente'],
            ['id' => 3, 'name' => 'Email'],
            ['id' => 4, 'name' => 'Telefone'],
            ['id' => 5, 'name' => 'WhatsApp'],
        ]);
    }

    private function ensureStatuses(): \Illuminate\Support\Collection
    {
        $this->call(StatusSeeder::class);

        return Status::query()->orderBy('id')->get();
    }

    private function ensurePriorities(): \Illuminate\Support\Collection
    {
        $priorities = [
            ['id' => 1, 'name' => 'Baixa', 'color' => '#22c55e'],
            ['id' => 2, 'name' => 'Média', 'color' => '#0ea5e9'],
            ['id' => 3, 'name' => 'Alta',  'color' => '#ef4444'],
        ];
        foreach ($priorities as $p) {
            Priority::updateOrCreate(['id' => $p['id']], $p);
        }

        return Priority::all();
    }

    private function ensureAgents(): \Illuminate\Support\Collection
    {
        $agentEmails = [
            ['email' => 'suporte1@amura.com.br', 'name' => 'Suporte Amura 1'],
            ['email' => 'suporte2@amura.com.br', 'name' => 'Suporte Amura 2'],
            ['email' => 'suporte3@amura.com.br', 'name' => 'Suporte Amura 3'],
        ];
        foreach ($agentEmails as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'active' => true,
                    'ticketit_agent' => true,
                    'department_id' => 1,
                ]
            );
        }

        return Agent::where('ticketit_agent', true)->get();
    }

    private function ensureSoftwares(): \Illuminate\Support\Collection
    {
        $this->call(SoftwareSeeder::class);

        return Software::query()
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    private function ensureCompanies(\Illuminate\Support\Collection $softwares): \Illuminate\Support\Collection
    {
        $companyNames = [
            ['Supermercado Bom Preço',  'Bom Preço', '08.123.456/0001-00'],
            ['Farmácia Saúde Total',    'Saúde Total', '09.234.567/0001-11'],
            ['Distribuidora Norte Sul', 'Norte Sul',  '10.345.678/0001-22'],
            ['Auto Peças Central',      'Central AP', '11.456.789/0001-33'],
            ['Padaria do Bairro',       'PadBairro',  '12.567.890/0001-44'],
            ['Loja da Moda Ltda',       'Moda Ltda',  '13.678.901/0001-55'],
            ['Const. Solidez',          'Solidez',    '14.789.012/0001-66'],
            ['Pet Shop Amigo Fiel',     'AmigoFiel',  '15.890.123/0001-77'],
        ];

        $softwares = $softwares->values();
        $companies = collect();

        foreach ($companyNames as $index => [$name, $trade, $cnpj]) {
            $softwareId = $softwares[$index % $softwares->count()]->id;

            $companies->push(Company::updateOrCreate(
                ['cnpj' => $cnpj],
                [
                    'name' => $name,
                    'trade_name' => $trade,
                    'is_active' => true,
                    'state_id' => 26, // SP
                    'software_id' => $softwareId,
                    'contact_name' => fake()->name(),
                ]
            ));
        }

        return $companies;
    }

    private function ensureCategories(): \Illuminate\Support\Collection
    {
        // Categoria pai (parent_id = 0, header = 0 tinyint)
        $existingParent = \DB::table('solutions_category')
            ->where('parent_id', 0)
            ->first();

        if ($existingParent) {
            $parentId = $existingParent->category_id;
        } else {
            $supportDepartmentId = \DB::table('user_department')
                ->where('name', 'like', '%Suporte%')
                ->orderBy('id')
                ->value('id');

            $parentId = \DB::table('solutions_category')->insertGetId([
                'parent_id' => 0,
                'sort_order' => 0,
                'status' => 1,
                'visible' => 1,
                'header' => 0,
                'department_id' => $supportDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \DB::table('solutions_category_description')->insertOrIgnore([
                ['category_id' => $parentId, 'name' => 'Suporte Técnico', 'description' => ''],
            ]);
        }

        $subIds = collect();

        foreach ($this->problems as $problemName) {
            // Verifica se subcategoria com esse nome já existe na tabela de descrição
            $existing = \DB::table('solutions_category_description')
                ->where('name', $problemName)
                ->first();

            if ($existing) {
                $cat = Category::find($existing->category_id);
                if ($cat) {
                    $subIds->push($cat);
                }

                continue;
            }

            // Cria a subcategoria (parent_id = $parentId, header = tinyint 0)
            // Herda department_id do pai para alinhar com o TicketDepartmentResolver.
            $parentDepartmentId = \DB::table('solutions_category')
                ->where('category_id', $parentId)
                ->value('department_id');

            $subId = \DB::table('solutions_category')->insertGetId([
                'parent_id' => $parentId,
                'sort_order' => 0,
                'status' => 1,
                'visible' => 1,
                'header' => 0,
                'department_id' => $parentDepartmentId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Nome vai na tabela de descrição
            \DB::table('solutions_category_description')->insert([
                'category_id' => $subId,
                'name' => $problemName,
                'description' => '',
            ]);

            $cat = Category::find($subId);
            if ($cat) {
                $subIds->push($cat);
            }
        }

        return $subIds->filter();
    }

    private function hasDailyProblemsSeedData(): bool
    {
        return Ticket::query()
            ->where('obs', self::DAILY_PROBLEMS_MARKER)
            ->whereNotNull('sub_category_id')
            ->exists();
    }

    private function hasImplementationSeedData(): bool
    {
        return DB::table('schedule_record')
            ->where('obs', 'like', self::IMPLEMENTATION_MARKER.'%')
            ->exists();
    }
}
