<?php

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\User\Setting;

describe('Condensed — abertura de chamado em nova aba', function () {
    it('renderiza os links de chamado com target blank quando a preferencia esta ativa', function () {
        $admin = actingAsAdmin();
        $agent = User::factory()->agent()->create();

        Setting::create([
            'user_id' => $admin->id,
            'slug' => 'open_ticket_new_tab',
            'value' => '1',
            'default' => '0',
        ]);

        $pendingTicket = Ticket::factory()->create([
            'agent_id' => null,
            'completed_at' => null,
            'status_id' => Ticket::STATUS_PENDING_ID,
        ]);

        $assignedTicket = Ticket::factory()->create([
            'agent_id' => $agent->id,
            'completed_at' => null,
            'status_id' => Ticket::STATUS_PENDING_ID,
        ]);

        $attendanceOnlyTicket = Ticket::factory()->create([
            'agent_id' => $agent->id,
            'status_id' => 3,
            'completed_at' => now()->subDays(5),
        ]);

        Attendance::factory()->create([
            'ticket_id' => $attendanceOnlyTicket->id,
            'user_id' => $admin->id,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $html = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->content();

        expect($html)
            ->toMatch(ticketAnchorPattern($pendingTicket->id))
            ->toMatch(ticketAnchorPattern($assignedTicket->id))
            ->toMatch(ticketAnchorPattern($attendanceOnlyTicket->id));
    });

    it('mantem os links de chamado na mesma aba quando a preferencia esta desativada', function () {
        $admin = actingAsAdmin();

        Setting::create([
            'user_id' => $admin->id,
            'slug' => 'open_ticket_new_tab',
            'value' => '0',
            'default' => '0',
        ]);

        $ticket = Ticket::factory()->create([
            'agent_id' => null,
            'completed_at' => null,
            'status_id' => Ticket::STATUS_PENDING_ID,
        ]);

        $html = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertSee(route('agent.ticket.show', $ticket->id), false)
            ->content();

        expect($html)->not->toMatch(ticketAnchorPattern($ticket->id));
    });
});

function ticketAnchorPattern(int $ticketId): string
{
    $href = preg_quote(route('agent.ticket.show', $ticketId), '/');

    return '/href="'.$href.'"[^>]*target="_blank"[^>]*rel="noopener noreferrer"/';
}
