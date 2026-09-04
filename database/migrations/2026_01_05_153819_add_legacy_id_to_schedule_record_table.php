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
            $table->bigInteger('legacy_id')
                  ->nullable()
                  ->after('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_record', function (Blueprint $table) {
            $table->dropColumn('legacy_id');
        });
    }
};
