<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerGroup;
use App\Models\User;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@admin.com')->first();
        if (! $admin) {
            $this->command->error('Usuário admin não encontrado. Rode o AdminUserSeeder primeiro.');
            return;
        }

        $groups = [
            'Pequenas Empresas',
            'Médias Empresas',
            'Grandes Empresas',
            'Startups',
            'E-commerce',
            'Varejo',
            'Indústria',
            'Serviços',
            'Tecnologia',
            'Grupo Exemplo',
        ];

        foreach ($groups as $name) {
            CustomerGroup::updateOrCreate(
                ['hash' => \Str::slug($name)],
                [
                    'user_id' => $admin->id,
                    'name'    => $name,
                    'status'  => true,
                ]
            );
        }

        $this->command->info('Customer groups criados com sucesso!');
    }
}
