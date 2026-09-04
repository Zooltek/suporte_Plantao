<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Define as novas colunas e suas configurações.
     */
    private function getNewColumns(): array
    {
        return [
            'contact_name'  => fn(Blueprint $t) => $t->string('contact_name')->nullable()->comment('Nome do contato principal'),
            'contact_email' => fn(Blueprint $t) => $t->string('contact_email')->nullable()->comment('E-mail do contato'),
            'telephone_2'   => fn(Blueprint $t) => $t->string('telephone_2')->nullable()->comment('Segundo telefone'),
            'observations'  => fn(Blueprint $t) => $t->text('observations')->nullable()->comment('Observações gerais'),
            'has_ecommerce' => fn(Blueprint $t) => $t->boolean('has_ecommerce')->default(false)->comment('Possui E-commerce'),
            'has_crm'       => fn(Blueprint $t) => $t->boolean('has_crm')->default(false)->comment('Possui CRM'),
            'has_tef'       => fn(Blueprint $t) => $t->boolean('has_tef')->default(false)->comment('Possui TEF'),
            'is_active'     => fn(Blueprint $t) => $t->boolean('is_active')->default(true)->comment('Cliente ativo'),
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach ($this->getNewColumns() as $columnName => $definition) {
                if (!Schema::hasColumn('customers', $columnName)) {
                    $definition($table);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = array_keys($this->getNewColumns());
            
            // O método dropColumn aceita um array de nomes de colunas
            $table->dropColumn($columns);
        });
    }
};
