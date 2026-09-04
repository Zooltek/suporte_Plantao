<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_record', function (Blueprint $table) {
            $table->boolean('resolved')->nullable()->after('release')
                ->comment('Atendimento resolvido: true=Sim, false=Não, null=Não informado');
            $table->boolean('has_nfe')->nullable()->after('resolved')
                ->comment('Possui NF-e vinculada ao atendimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_record', function (Blueprint $table) {
            $table->dropColumn(['resolved', 'has_nfe']);
        });
    }
};
