<?php

use App\Support\Tickets\TicketStatusCatalog;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;
use Illuminate\Support\Facades\DB;

describe('StatusSeeder — catálogo canônico de status', function () {
    it('sincroniza os status operacionais esperados com as flags corretas', function () {
        $this->seed(StatusSeeder::class);

        $statuses = DB::table('ticketit_statuses')
            ->whereIn('id', array_column(TicketStatusCatalog::definitions(), 'id'))
            ->orderBy('id')
            ->get(['id', 'name', 'is_terminal', 'requires_schedule', 'requires_solution', 'requires_agent']);

        expect($statuses)->toHaveCount(count(TicketStatusCatalog::definitions()));

        foreach (TicketStatusCatalog::definitions() as $definition) {
            $status = $statuses->firstWhere('id', $definition['id']);

            expect($status)->not->toBeNull()
                ->and($status->name)->toBe($definition['name'])
                ->and((bool) $status->is_terminal)->toBe($definition['is_terminal'])
                ->and((bool) $status->requires_schedule)->toBe($definition['requires_schedule'])
                ->and((bool) $status->requires_solution)->toBe($definition['requires_solution'])
                ->and((bool) $status->requires_agent)->toBe($definition['requires_agent']);
        }
    });
});
