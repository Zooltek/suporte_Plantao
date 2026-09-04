<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Company;
use App\Models\Helpdesk\Ticketit\Category as TicketitAgentCategory;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketitSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Origens do Ticketit (necessário para FK ticketit.origin_id)
            DB::table('ticketit_origin')->insertOrIgnore([
                ['id' => 1, 'name' => 'Sistema Interno'],
                ['id' => 2, 'name' => 'Portal do Cliente'],
                ['id' => 3, 'name' => 'Email'],
                ['id' => 4, 'name' => 'Telefone'],
                ['id' => 5, 'name' => 'WhatsApp'],
            ]);
            $originIds = DB::table('ticketit_origin')->pluck('id')->all();

            $this->call(StatusSeeder::class);

            // Prioridades
            $priorities = [
                ['id' => 1, 'name' => 'Baixa', 'color' => '#22c55e'],
                ['id' => 2, 'name' => 'Média', 'color' => '#0ea5e9'],
                ['id' => 3, 'name' => 'Alta',  'color' => '#ef4444'],
            ];
            foreach ($priorities as $priority) {
                Priority::updateOrCreate(['id' => $priority['id']], $priority);
            }

            $ticketCategories = $this->ensureTicketCategories();
            $agentCategories = $this->ensureAgentCategories();
            $companyId = Company::query()->inRandomOrder()->value('id') ?? Company::factory()->create()->id;

            // Agentes/Admins
            $agents = collect([
                User::firstOrCreate(
                    ['email' => 'agent1@example.com'],
                    [
                        'name' => 'Agente 1',
                        'password' => bcrypt('password'),
                        'department_id' => 1, // Suporte Técnico
                        'active' => true,
                        'ticketit_agent' => true,
                    ]
                ),
                User::firstOrCreate(
                    ['email' => 'agent2@example.com'],
                    [
                        'name' => 'Agente 2',
                        'password' => bcrypt('password'),
                        'department_id' => 1, // Suporte Técnico
                        'active' => true,
                        'ticketit_agent' => true,
                    ]
                ),
            ]);

            // Usuários finais
            $customers = User::factory()->count(6)->create();

            // Vincula agentes às categorias (pivot)
            $agentCategoryIds = $agentCategories->pluck('id')->values();
            foreach ($agents as $agent) {
                foreach ($agentCategoryIds as $catId) {
                    DB::table('ticketit_categories_users')->updateOrInsert([
                        'category_id' => $catId,
                        'user_id' => $agent->id,
                    ], []);
                }
            }

            // Tickets — mistura categorias pai com subcategorias setoriais
            // (Comercial e Financeiro) para que os relatórios e o backfill de
            // departamento por categoria tenham material a processar no DEV.
            $ticketCategoryIds = $ticketCategories->pluck('category_id')->values();
            $sectorialSubIds = Category::query()
                ->whereHas('description', fn ($q) => $q->whereIn('name', ['Comercial', 'Financeiro']))
                ->pluck('category_id')
                ->values();
            $allowedCategoryIds = $ticketCategoryIds->merge($sectorialSubIds)->unique()->values();

            $ticketsToCreate = 18;
            for ($i = 0; $i < $ticketsToCreate; $i++) {
                $ticket = Ticket::factory()->create([
                    'status_id' => $this->pickWeightedStatus(),
                    'priority_id' => Priority::inRandomOrder()->value('id'),
                    'category_id' => $allowedCategoryIds->random(),
                    'user_id' => $customers->random()->id,
                    'agent_id' => $agents->random()->id,
                    'origin_id' => $originIds[array_rand($originIds)] ?? 1,
                    'company_id' => $companyId,
                ]);

                // Marca alguns como concluídos para popular métricas
                if (in_array($ticket->status_id, [3])) {
                    $ticket->update(['completed_at' => now()->subDays(rand(1, 20))]);
                }
            }

            // Atualiza alguns tickets para que apareçam como 'em andamento' recentes
            Ticket::where('status_id', '!=', 3)
                ->inRandomOrder()
                ->take(5)
                ->get()
                ->each(fn ($ticket) => $ticket->touch());
        });
    }

    private function pickWeightedStatus(): int
    {
        // Mais abertos/pedentes para dar variação às métricas
        $pool = [1, 1, 1, 1, 2, 2, 3, 4, 4];

        return $pool[array_rand($pool)];
    }

    private function ensureTicketCategories(): Collection
    {
        $categories = Category::query()->root()->orderBy('category_id')->get();

        if ($categories->isNotEmpty()) {
            return $categories;
        }

        $fallbacks = [
            ['name' => 'Desenvolvimento', 'priority' => Category::PRIORITY_HIGH],
            ['name' => 'Infraestrutura', 'priority' => Category::PRIORITY_HIGH],
            ['name' => 'Atendimento', 'priority' => Category::PRIORITY_LOW],
        ];

        return collect($fallbacks)->map(function (array $fallback): Category {
            $category = Category::query()->create([
                'parent_id' => 0,
                'priority' => $fallback['priority'],
            ]);

            CategoryDescription::query()->updateOrCreate(
                ['category_id' => $category->category_id],
                [
                    'name' => $fallback['name'],
                    'permalink' => Str::slug($fallback['name']),
                    'description' => $fallback['name'],
                ]
            );

            return $category->fresh();
        });
    }

    private function ensureAgentCategories(): Collection
    {
        $definitions = [
            ['name' => 'Helpdesk', 'color' => '#3b82f6'],
            ['name' => 'Infra', 'color' => '#9333ea'],
            ['name' => 'Financeiro', 'color' => '#ec4899'],
        ];

        return collect($definitions)->map(function (array $definition): TicketitAgentCategory {
            return TicketitAgentCategory::query()->firstOrCreate(
                ['name' => $definition['name']],
                ['color' => $definition['color']]
            );
        });
    }
}
