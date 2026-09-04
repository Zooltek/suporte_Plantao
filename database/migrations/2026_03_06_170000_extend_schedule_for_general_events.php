<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->string('title')->nullable()->after('module_id');
            $table->string('kind', 30)->default('client')->after('title');
            $table->unsignedBigInteger('module_id')->nullable()->change();
            $table->string('contact')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('module_id')->nullable(false)->change();
            $table->string('contact')->nullable(false)->change();
            $table->dropColumn(['title', 'kind']);
        });
    }
};
