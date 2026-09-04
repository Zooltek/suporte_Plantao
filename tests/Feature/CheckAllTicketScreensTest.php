<?php

namespace Tests\Feature;

use App\Models\Ticket\Ticket;
use App\Models\User;
use Tests\TestCase;

class CheckAllTicketScreensTest extends TestCase
{
    public function test_all_ticket_screens_render_without_500(): void
    {
        $admin = User::where('ticketit_admin', 1)->first() ?? User::factory()->create([
            'ticketit_admin' => 1,
            'ticketit_agent' => 1,
            'active' => 1,
            'must_change_password' => 0,
        ]);

        $ticket = Ticket::first() ?? Ticket::factory()->create([
            'agent_id' => $admin->id,
            'status_id' => 1,
        ]);

        $routes = [
            '/support/tickets',
            '/support/tickets?mine=1',
            '/support/tickets?unassigned=1',
            '/support/tickets?order=1',
            '/support/tickets?order=2',
            '/support/tickets?order=3',
            '/support/tickets?date_from=' . date('Y-m-d') . '&date_to=' . date('Y-m-d'),
            '/support/tickets/create',
            '/support/helper',
            '/support/calendar/condensed',
            '/support/tickets/totalizadores',
            "/support/tickets/{$ticket->id}",
            "/support/tickets/{$ticket->id}/edit",
            "/support/tickets/{$ticket->id}/attendances-partial",
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($admin, 'admin')->get($url);
            if ($response->status() !== 200) {
                dump("URL $url returned status " . $response->status());
                if ($response->exception) {
                    dump($response->exception->getMessage());
                    dump($response->exception->getFile() . ':' . $response->exception->getLine());
                }
            }
            $this->assertEquals(200, $response->status(), "Route $url failed with status " . $response->status());
        }
    }
}
