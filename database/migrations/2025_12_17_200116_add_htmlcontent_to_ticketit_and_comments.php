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
            $table->longText('html')->nullable()->after('content');
        });

        Schema::table('ticketit_comments', function (Blueprint $table) {
            $table->longText('html')->nullable()->after('content');
            $table->longText('content')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropColumn('html');
        });

        Schema::table('ticketit_comments', function (Blueprint $table) {
            $table->dropColumn('html');
            $table->text('content')->change();
        });
    }
};
