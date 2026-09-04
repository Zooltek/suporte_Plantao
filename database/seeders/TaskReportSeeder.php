<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Popula tarefas vinculadas a projetos reais para alimentar os relatórios.
 * Cobre os cenários: por-cliente, por-modulo e relatorio Carlos.
 * Idempotente: verifica existência antes de criar.
 */
class TaskReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('ticketit_admin', 1)->orderBy('id')->first()
            ?? User::orderBy('id')->firstOrFail();

        $customers = Customer::inRandomOrder()->limit(5)->get();
        if ($customers->isEmpty()) {
            $this->command->warn('TaskReportSeeder: sem clientes cadastrados — pulando.');
            return;
        }

        $projectNames = ['EasyMaster', 'EasyControl', 'AmuraWeb', 'Plenus'];
        $projects = collect($projectNames)->map(fn ($name) =>
            Project::firstOrCreate(
                ['name' => $name],
                ['color' => '#6366f1', 'user_id' => $admin->id, 'status' => 1]
            )
        );

        // Labels (módulos) para o relatório Carlos e por-modulo
        $moduleNames = [
            'Instalação', 'Cadastro', 'Estoque', 'Vendas',
            'Financeiro', 'Contábil', 'OS e Orçamento', 'Outros',
        ];

        $rootLabel = Label::firstOrCreate(
            ['name' => 'Módulos ERP', 'parent_id' => null],
            ['user_id' => $admin->id, 'is_active' => true]
        );

        $labels = collect($moduleNames)->map(fn ($name) =>
            Label::firstOrCreate(
                ['name' => $name, 'parent_id' => $rootLabel->id],
                ['user_id' => $admin->id, 'is_active' => true]
            )
        );

        $statuses = ['new', 'pen', 'pro', 'sto', 'don'];

        foreach ($projects as $project) {
            foreach ($customers->take(3) as $customer) {
                foreach ($statuses as $status) {
                    $task = Task::factory()
                        ->assignedTo($admin)
                        ->withProject($project)
                        ->state([
                            'status'      => $status,
                            'customer_id' => $customer->id,
                            'parent_id'   => null,
                            'title'       => "[{$project->name}] Tarefa {$status} — {$customer->name}",
                        ])
                        ->create();

                    // Associa um label (módulo) aleatório
                    $task->labels()->syncWithoutDetaching([$labels->random()->id]);
                }
            }
        }

        $total = Task::whereNotNull('project_id')->count();
        $this->command->info("TaskReportSeeder: {$total} tarefas com projeto no banco.");
    }
}
