<?php

use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;

function tsep_ticketWithConversation(): Ticket
{
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $ticket = Ticket::factory()->create(['company_id' => $company->id]);

    WhatsAppConversation::factory()->create([
        'ticket_id' => $ticket->id,
        'company_id' => $company->id,
    ]);

    return $ticket;
}

describe('TicketShow — galeria de emojis no chat WhatsApp', function () {

    it('renderiza o botão de emoji acessível com aria-haspopup="dialog"', function () {
        actingAsAdmin();
        $ticket = tsep_ticketWithConversation();

        $response = $this->get(route('agent.ticket.show', $ticket->id))->assertOk();

        $response->assertSee('data-whatsapp-emoji', false);
        $response->assertSee('aria-haspopup="dialog"', false);
        $response->assertSee('Galeria de emojis', false);
    });

    it('não usa mais o título antigo "Inserir emoji"', function () {
        actingAsAdmin();
        $ticket = tsep_ticketWithConversation();

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertDontSee('title="Inserir emoji"', false);
    });

});
