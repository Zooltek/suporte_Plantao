<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit_attachments', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('name')
                  ->comment('Nome original do arquivo enviado pelo usuário');
            $table->string('disk_path')->nullable()->after('original_name')
                  ->comment('Caminho relativo em storage/app/public');
            $table->unsignedBigInteger('size')->default(0)->after('disk_path')
                  ->comment('Tamanho em bytes');
        });
    }

    public function down(): void
    {
        Schema::table('ticketit_attachments', function (Blueprint $table) {
            $table->dropColumn(['original_name', 'disk_path', 'size']);
        });
    }
};
