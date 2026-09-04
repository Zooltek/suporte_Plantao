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
        if (Schema::hasTable('solutions_category_description') && !Schema::hasColumn('solutions_category_description', 'permalink')) {
            Schema::table('solutions_category_description', function (Blueprint $table) {
                $table->string('permalink')->nullable()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('solutions_category_description') && Schema::hasColumn('solutions_category_description', 'permalink')) {
            Schema::table('solutions_category_description', function (Blueprint $table) {
                $table->dropColumn('permalink');
            });
        }
    }
};

