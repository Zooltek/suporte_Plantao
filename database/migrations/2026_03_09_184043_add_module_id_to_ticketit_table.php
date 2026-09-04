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
            $table->unsignedBigInteger('module_id')->nullable()->after('sub_category_id');
            $table->foreign('module_id')->references('id')->on('company_module_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn('module_id');
        });
    }
};
