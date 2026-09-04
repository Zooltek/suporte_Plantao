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
        Schema::table('ticketit', function (Blueprint $table) {
            $table->json('rat_responses')->nullable()->after('obs');
        });
    }

    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropColumn('rat_responses');
        });
    }
};
