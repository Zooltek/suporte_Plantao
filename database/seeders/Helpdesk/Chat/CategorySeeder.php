<?php

namespace Database\Seeders\Helpdesk\Chat;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::firstOrCreate(['name' => 'Suporte Técnico'], ['color' => '#6c757d']);
        Category::firstOrCreate(['name' => 'Financeiro'], ['color' => '#28a745']);
        Category::firstOrCreate(['name' => 'Infraestrutura'], ['color' => '#17a2b8']);

        Category::factory()->count(5)->create();
    }
}
