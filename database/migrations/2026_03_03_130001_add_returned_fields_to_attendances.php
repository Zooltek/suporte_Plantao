<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('returned_by')->nullable()->after('return_cel')->comment('Usuário que registrou o retorno');
            $table->timestamp('returned_at')->nullable()->after('returned_by')->comment('Data/hora do registro do retorno');

            $table->foreign('returned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropColumn(['returned_by', 'returned_at']);
        });
    }
};
