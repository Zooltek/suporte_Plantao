<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Crm\Feedback;
use App\Models\User;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there is at least one user to reference
        $admin = User::firstOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Admin Cassiano',
                'password' => bcrypt('password'),
                'ticketit_admin' => true,
                'ticketit_agent' => true,
            ]
        );

        $customerId = 1; // fallback customer id; adjust if customers seeded elsewhere

        // Criação de formulário padrão para evitar violação de Foreign Key no crm_feedback
        $form = \App\Models\Crm\Feedback\Form::firstOrCreate(
            ['id' => 1],
            ['name' => 'Formulário CRM Padrão']
        );

        Feedback::firstOrCreate([
            'content' => 'Feedback de teste - funcionalidade CRM',
            'suggestions' => 'Melhorar instrucoes na tela',
            'complaint' => null,
            'contact' => 'cliente@teste.com',
            'version' => '1.0.0',
            'release' => '2026-01-07',
            'customer_id' => $customerId,
            'user_id' => $admin->id,
            'form_id' => $form->id,
            'status' => 'open',
        ]);

        Feedback::firstOrCreate([
            'content' => 'Teste de cancelamento',
            'suggestions' => null,
            'complaint' => 'Problema no botao de salvar',
            'contact' => 'cliente2@teste.com',
            'version' => '1.0.1',
            'release' => '2026-01-07',
            'customer_id' => $customerId,
            'user_id' => $admin->id,
            'form_id' => $form->id,
            'status' => 'fin',
        ]);
        $this->command->info('Feedbacks de teste criados/atualizados com sucesso!');
    }
}
