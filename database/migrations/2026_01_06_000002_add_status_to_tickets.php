<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('ticketit')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->enum('status', ['Aberto','Fechado','Aguardando','Em Atendimento','Rejeitado','Aguardando Cliente'])
                    ->default('Aberto')
                    ->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('ticketit')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
