<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks_projects', 'schedule_project_name')) {
                $table->string('schedule_project_name')->nullable()->after('name')->index();
            }
        });

        Schema::table('labels', function (Blueprint $table) {
            if (! Schema::hasColumn('labels', 'schedule_module_id')) {
                $table->foreignId('schedule_module_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('schedule_record_module')
                    ->nullOnDelete()
                    ->unique();
            }
        });
    }

    public function down(): void
    {
        Schema::table('labels', function (Blueprint $table) {
            if (Schema::hasColumn('labels', 'schedule_module_id')) {
                $table->dropConstrainedForeignId('schedule_module_id');
            }
        });

        Schema::table('tasks_projects', function (Blueprint $table) {
            if (Schema::hasColumn('tasks_projects', 'schedule_project_name')) {
                $table->dropIndex(['schedule_project_name']);
                $table->dropColumn('schedule_project_name');
            }
        });
    }
};
