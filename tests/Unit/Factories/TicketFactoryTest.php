<?php

use App\Models\Ticket\Ticket;

describe('TicketFactory', function () {

    it('gera tickets com category_id compatível com solutions_category.category_id', function () {
        $ticket = Ticket::factory()->create();

        $ticket->refresh()->load('category');

        expect($ticket->category)->not->toBeNull()
            ->and((int) $ticket->category_id)->toBe((int) $ticket->category->category_id);
    });
});
