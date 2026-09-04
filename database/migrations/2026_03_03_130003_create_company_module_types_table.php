<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_module_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed com os módulos existentes (hardcoded na view history.blade.php)
        DB::table('company_module_types')->insert([
            ['name' => 'Caixa Autom.',  'slug' => 'caixa_automatico', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Aplicativo',    'slug' => 'aplicativo',        'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uninf Rede',    'slug' => 'uninf_rede',        'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Siga Vez',      'slug' => 'siga_vaz',          'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ftp Próprio',   'slug' => 'ftp_proprio',       'is_active' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'E-commerce',    'slug' => 'ecommerce',         'is_active' => true, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('company_module_types');
    }
};
