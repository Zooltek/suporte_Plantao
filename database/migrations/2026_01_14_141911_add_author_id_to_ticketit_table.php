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
        if (Schema::hasTable('ticketit')) {
            Schema::table('ticketit', function (Blueprint $table) {
                if (!Schema::hasColumn('ticketit', 'author_id')) {
                    $table->foreignId('author_id')->nullable()->constrained('users')->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ticketit', 'author_id')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->dropForeign(['author_id']);
                $table->dropColumn('author_id');
            });
        }
    }
};
