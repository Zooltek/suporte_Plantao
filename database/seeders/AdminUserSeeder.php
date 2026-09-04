<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Busca um departamento padrão que não seja o CRM
        $department = Department::where('id', '!=', 3)->first()
                      ?? Department::firstOrCreate(['name' => 'TI']);

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name'           => 'Admin System',
                'password'       => Hash::make('password'),
                'ticketit_admin' => true,
                'ticketit_agent' => true,
                'department_id'  => $department->id,
                'active'         => true,
            ]
        );

        $this->command->info('Admin principal (admin@admin.com) criado com sucesso!');
    }
}
