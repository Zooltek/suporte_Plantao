<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Popula tarefas representativas para a rota /tasks/mobile.
 *
 * Cenários cobertos:
 *  - Tarefas abertas em todos os status (new, pen, pro, sto)
 *  - Tarefas finalizadas (don, tdo) e canceladas/rejeitadas
 *  - Tarefas com prazo vencido
 *  - Usuário como responsável, autor e testador (testa scopeMine)
 *  - Tarefas com e sem projeto / cliente
 */
class MobileTaskSeeder extends Seeder
{
    public function run(): void
    {
        // Usa o admin com email admin@admin.com, ou o primeiro com ticketit_admin=1
        $user = User::where('email', 'admin@admin.com')->first()
            ?? User::where('ticketit_admin', 1)->orderBy('id')->first()
            ?? User::orderBy('id')->firstOrFail();

        $other = User::where('id', '!=', $user->id)->first() ?? $user;
        $customer = Customer::query()->first();
        $project  = Project::query()->first() ?? Project::create([
            'name'    => 'Projeto Demo Mobile',
            'color'   => '#6366f1',
            'user_id' => $user->id,
            'status'  => 1,
        ]);

        $this->command->info("Usando usuário: {$user->name} (id={$user->id})");

        // 1. Tarefas abertas — usuário como RESPONSÁVEL
        foreach (['new', 'pen', 'pro', 'sto'] as $status) {
            Task::factory()
                ->assignedTo($user)
                ->authoredBy($other)
                ->state(['status' => $status, 'customer_id' => $customer?->id])
                ->create(['title' => "[{$status}] Tarefa aberta — responsável"]);
        }

        // 2. Tarefas finalizadas / canceladas — usuário como RESPONSÁVEL
        foreach (['don', 'tdo', 'can', 'rej'] as $status) {
            Task::factory()
                ->assignedTo($user)
                ->authoredBy($other)
                ->done()
                ->state(['status' => $status, 'customer_id' => $customer?->id])
                ->create(['title' => "[{$status}] Tarefa encerrada — responsável"]);
        }

        // 3. Tarefas com prazo vencido — usuário como RESPONSÁVEL
        Task::factory()
            ->count(3)
            ->assignedTo($user)
            ->authoredBy($other)
            ->overdue()
            ->create(['title' => '[overdue] Tarefa atrasada — responsável']);

        // 4. Tarefas abertas — usuário como AUTOR (testa orWhere author_id)
        Task::factory()
            ->count(3)
            ->open()
            ->authoredBy($user)
            ->assignedTo($other)
            ->state(['customer_id' => $customer?->id])
            ->create(['title' => '[autor] Tarefa onde usuário é autor']);

        // 5. Tarefas — usuário como TESTADOR (testa orWhere tester_id)
        Task::factory()
            ->count(3)
            ->open()
            ->testedBy($user)
            ->assignedTo($other)
            ->authoredBy($other)
            ->state(['customer_id' => $customer?->id])
            ->create(['title' => '[tester] Tarefa onde usuário é testador']);

        // 6. Tarefas vinculadas a projeto — usuário como RESPONSÁVEL
        Task::factory()
            ->count(4)
            ->open()
            ->assignedTo($user)
            ->authoredBy($other)
            ->withProject($project)
            ->state(['customer_id' => $customer?->id])
            ->create(['title' => '[projeto] Tarefa com projeto vinculado']);

        // 7. Tarefa sem cliente nem projeto — edge case de exibição
        Task::factory()
            ->count(2)
            ->open()
            ->assignedTo($user)
            ->authoredBy($other)
            ->state(['customer_id' => null, 'project_id' => null])
            ->create(['title' => '[sem-cliente] Tarefa sem cliente/projeto']);

        $total = Task::where('user_id', $user->id)
            ->orWhere('author_id', $user->id)
            ->orWhere('tester_id', $user->id)
            ->count();

        $this->command->info("MobileTaskSeeder concluído. Total de tarefas do usuário: {$total}");
    }
}
