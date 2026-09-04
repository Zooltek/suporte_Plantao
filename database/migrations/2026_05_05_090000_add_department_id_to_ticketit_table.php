<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ticketit', 'department_id')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('agent_id')
                    ->constrained('user_department')
                    ->nullOnDelete();
            });
        }

        $supportDepartmentId = DB::table('user_department')
            ->where('name', 'like', '%Suporte%')
            ->value('id');

        if ($supportDepartmentId === null) {
            $supportDepartmentId = DB::table('user_department')->insertGetId([
                'name' => 'Suporte Técnico',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('ticketit')
            ->whereNull('department_id')
            ->whereNotNull('agent_id')
            ->orderBy('id')
            ->chunkById(500, function ($tickets): void {
                foreach ($tickets as $ticket) {
                    $departmentId = DB::table('users')
                        ->where('id', $ticket->agent_id)
                        ->value('department_id');

                    if ($departmentId) {
                        DB::table('ticketit')
                            ->where('id', $ticket->id)
                            ->update(['department_id' => $departmentId]);
                    }
                }
            });

        DB::table('ticketit')
            ->whereNull('department_id')
            ->update(['department_id' => $supportDepartmentId]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ticketit', 'department_id')) {
            return;
        }

        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
