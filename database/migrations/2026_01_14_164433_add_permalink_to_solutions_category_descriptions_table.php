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
        if (Schema::hasTable('solutions_category_descriptions')) {
            Schema::table('solutions_category_descriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('solutions_category_descriptions', 'permalink')) {
                    $table->text('permalink')->nullable()->after('name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('solutions_category_descriptions')) {
            Schema::table('solutions_category_descriptions', function (Blueprint $table) {
                if (Schema::hasColumn('solutions_category_descriptions', 'permalink')) {
                    $table->dropColumn('permalink');
                }
            });
        }
    }
};
