<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Helpdesk\Ticketit\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ProfilesUserSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            'admin@system.com' => [
                'name' => 'Admin System',
                'department_id' => 10,
                'ticketit_admin' => true,
                'ticketit_agent' => true,
            ],
            'agent@system.com' => [
                'name' => 'Agent System',
                'department_id' => 1,
                'ticketit_agent' => true,
            ],
            'crm@system.com' => [
                'name' => 'CRM System',
                'department_id' => 3,
            ],
            'client@system.com' => [
                'name' => 'Client System',
                'department_id' => 1,
            ],
        ];

        foreach ($profiles as $email => $data) {
            User::updateOrCreate(
                ['email' => $email],
                array_merge([
                    'password'           => Hash::make('password'),
                    'active'             => true,
                    'ticketit_admin'     => false,
                    'ticketit_agent'     => false,
                    'email_verified_at'  => Carbon::now(),
                ], $data)
            );
        }

        // Configuração global do Ticketit
        Setting::updateOrCreate(['slug' => 'theme'], ['value' => 'ocean', 'default' => 'light']);

        $this->command->info('Perfis de sistema (@system.com) criados com sucesso!');
    }
}
