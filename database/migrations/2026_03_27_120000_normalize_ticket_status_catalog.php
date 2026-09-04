<?php

use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $existingStatuses = DB::table('ticketit_statuses')
                ->orderBy('id')
                ->get(['id', 'name', 'requires_schedule']);

            foreach (TicketStatusCatalog::definitions() as $status) {
                DB::table('ticketit_statuses')->updateOrInsert(
                    ['id' => $status['id']],
                    [
                        'name' => $status['name'],
                        'color' => $status['color'],
                        'is_terminal' => $status['is_terminal'],
                        'requires_schedule' => $status['requires_schedule'],
                        'requires_solution' => $status['requires_solution'],
                        'requires_agent' => $status['requires_agent'],
                        'updated_at' => now(),
                    ]
                );
            }

            foreach ($existingStatuses as $status) {
                $this->remapLegacyTickets((int) $status->id, (string) $status->name, (bool) $status->requires_schedule);
            }

            DB::table('ticketit_statuses')
                ->whereNotIn('id', array_column(TicketStatusCatalog::definitions(), 'id'))
                ->delete();

            $this->normalizeCompletedAt();
        });
    }

    public function down(): void
    {
        // Migração irreversível: consolida o catálogo legado em um conjunto canônico.
    }

    private function remapLegacyTickets(int $fromStatusId, string $name, bool $requiresSchedule): void
    {
        $canonicalIds = array_column(TicketStatusCatalog::definitions(), 'id');
        $normalizedName = TicketStatusCatalog::normalizeName($name);

        if ($requiresSchedule || $normalizedName === 'visita tecnica') {
            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->update(['status_id' => TicketStatusCatalog::TECHNICAL_VISIT_ID]);

            return;
        }

        if ($normalizedName === 'resolvido') {
            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->update(['status_id' => TicketStatusCatalog::RESOLVED_ID]);

            return;
        }

        if (in_array($normalizedName, ['nao resolvido', 'fechado', 'cancelado'], true)) {
            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->update(['status_id' => TicketStatusCatalog::UNRESOLVED_ID]);

            return;
        }

        if ($normalizedName === 'pendente') {
            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->update(['status_id' => TicketStatusCatalog::PENDING_ID]);

            return;
        }

        if ($normalizedName === 'solicitacao') {
            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->update(['status_id' => TicketStatusCatalog::REQUEST_ID]);

            return;
        }

        if (! in_array($fromStatusId, $canonicalIds, true) || $normalizedName === 'aberto') {
            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->where(function ($query): void {
                    $query
                        ->whereNull('agent_id')
                        ->orWhere('agent_id', 0);
                })
                ->update([
                    'status_id' => TicketStatusCatalog::PENDING_ID,
                    'completed_at' => null,
                ]);

            DB::table('ticketit')
                ->where('status_id', $fromStatusId)
                ->whereNotNull('agent_id')
                ->where('agent_id', '!=', 0)
                ->update([
                    'status_id' => TicketStatusCatalog::IN_PROGRESS_ID,
                    'completed_at' => null,
                ]);
        }
    }

    private function normalizeCompletedAt(): void
    {
        DB::table('ticketit')
            ->whereIn('status_id', [
                TicketStatusCatalog::OPEN_ID,
                TicketStatusCatalog::IN_PROGRESS_ID,
                TicketStatusCatalog::PENDING_ID,
                TicketStatusCatalog::TECHNICAL_VISIT_ID,
                TicketStatusCatalog::REQUEST_ID,
            ])
            ->update(['completed_at' => null]);

        DB::table('ticketit')
            ->whereIn('status_id', [
                TicketStatusCatalog::RESOLVED_ID,
                TicketStatusCatalog::UNRESOLVED_ID,
            ])
            ->whereNull('completed_at')
            ->orderBy('id')
            ->get(['id', 'updated_at', 'created_at'])
            ->each(function (object $ticket): void {
                DB::table('ticketit')
                    ->where('id', $ticket->id)
                    ->update([
                        'completed_at' => $ticket->updated_at ?? $ticket->created_at ?? now(),
                    ]);
            });
    }
};
