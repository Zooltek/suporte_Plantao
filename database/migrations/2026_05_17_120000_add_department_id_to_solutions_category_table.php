<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('solutions_category', 'department_id')) {
            return;
        }

        Schema::table('solutions_category', function (Blueprint $table): void {
            $table->unsignedBigInteger('department_id')
                ->nullable()
                ->after('priority');

            $table->foreign('department_id')
                ->references('id')
                ->on('user_department')
                ->nullOnDelete();

            $table->index('department_id', 'solutions_category_department_id_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('solutions_category', 'department_id')) {
            return;
        }

        Schema::table('solutions_category', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropIndex('solutions_category_department_id_index');
            $table->dropColumn('department_id');
        });
    }
};
