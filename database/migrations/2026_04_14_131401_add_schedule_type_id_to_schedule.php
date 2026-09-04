<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->foreignId('schedule_type_id')
                ->nullable()
                ->after('kind')
                ->constrained('schedule_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_type_id');
        });
    }
};
