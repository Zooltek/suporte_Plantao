<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $statuses = DB::table('ticketit_statuses')
                ->select(['id', 'name'])
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($status) => mb_strtolower(trim((string) $status->name)));

            foreach ($statuses as $group) {
                if ($group->count() <= 1) {
                    continue;
                }

                $canonicalId = (int) $group->first()->id;
                $duplicateIds = $group
                    ->skip(1)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all();

                if ($duplicateIds === []) {
                    continue;
                }

                DB::table('ticketit')
                    ->whereIn('status_id', $duplicateIds)
                    ->update(['status_id' => $canonicalId]);

                DB::table('ticketit_statuses')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }
        });
    }

    public function down(): void
    {
        // Migração irreversível: consolida registros duplicados em um status canônico.
    }
};
