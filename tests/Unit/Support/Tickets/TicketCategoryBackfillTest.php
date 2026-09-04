<?php

use App\Models\Category;
use App\Models\Ticket\Ticket;
use App\Support\Tickets\TicketCategoryBackfill;
use Illuminate\Support\Facades\DB;

describe('TicketCategoryBackfill', function () {

    it('normaliza a categoria pai a partir da subcategoria e preserva o legado', function () {
        $root = Category::factory()->create(['parent_id' => 0]);
        $child = Category::factory()->create(['parent_id' => $root->category_id]);

        $ticket = Ticket::factory()->create([
            'category_id' => 999,
            'sub_category_id' => $child->category_id,
            'legacy_ticket_category_id' => null,
        ]);

        $summary = (new TicketCategoryBackfill(DB::connection()))->run();

        expect($summary['snapshotted'])->toBe(1)
            ->and($summary['normalized'])->toBe(1)
            ->and($summary['cleared_invalid_subcategories'])->toBe(0);

        $ticket->refresh();

        expect((int) $ticket->legacy_ticket_category_id)->toBe(999)
            ->and((int) $ticket->category_id)->toBe($root->category_id)
            ->and((int) $ticket->sub_category_id)->toBe($child->category_id);
    });

    it('limpa subcategoria inválida quando um registro raiz foi salvo no campo de subcategoria', function () {
        $root = Category::factory()->create(['parent_id' => 0]);

        $ticket = Ticket::factory()->create([
            'category_id' => $root->category_id,
            'sub_category_id' => $root->category_id,
            'legacy_ticket_category_id' => null,
        ]);

        $summary = (new TicketCategoryBackfill(DB::connection()))->run();

        expect($summary['cleared_invalid_subcategories'])->toBe(1);

        $ticket->refresh();

        expect($ticket->sub_category_id)->toBeNull();
    });
});
