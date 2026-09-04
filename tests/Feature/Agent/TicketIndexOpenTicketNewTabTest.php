<?php

use App\Models\Ticket\Ticket;
use App\Models\User\Setting;

describe('Masterlist — abertura de chamado em nova aba', function () {
    it('renderiza o link do chamado com target blank quando a preferencia esta ativa', function () {
        $admin = actingAsAdmin();

        Setting::create([
            'user_id' => $admin->id,
            'slug'    => 'open_ticket_new_tab',
            'value'   => '1',
            'default' => '0',
        ]);

        $ticket = Ticket::factory()->create([
            'agent_id'     => null,
            'completed_at' => null,
            'status_id'    => Ticket::STATUS_PENDING_ID,
        ]);

        $html = $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->content();

        $hrefShow   = preg_quote(route('agent.ticket.show', $ticket->id), '/');
        $hrefCreate = preg_quote(route('agent.ticket.create'), '/');

        expect($html)
            ->toMatch('/href="' . $hrefShow . '"[^>]*target="_blank"[^>]*rel="noopener noreferrer"/s')
            ->toMatch('/href="' . $hrefCreate . '"[^>]*target="_blank"[^>]*rel="noopener noreferrer"/s');
    });

    it('preferencia inexistente assume default ON e abre em nova aba', function () {
        actingAsAdmin();

        $ticket = Ticket::factory()->create([
            'agent_id'     => null,
            'completed_at' => null,
            'status_id'    => Ticket::STATUS_PENDING_ID,
        ]);

        $html = $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->content();

        $hrefShow = preg_quote(route('agent.ticket.show', $ticket->id), '/');

        expect($html)->toMatch('/href="' . $hrefShow . '"[^>]*target="_blank"/s');
    });

    it('mantem o link do chamado na mesma aba quando o usuario desativa explicitamente', function () {
        $admin = actingAsAdmin();

        Setting::create([
            'user_id' => $admin->id,
            'slug'    => 'open_ticket_new_tab',
            'value'   => '0',
            'default' => '0',
        ]);

        $ticket = Ticket::factory()->create([
            'agent_id'     => null,
            'completed_at' => null,
            'status_id'    => Ticket::STATUS_PENDING_ID,
        ]);

        $html = $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertSee(route('agent.ticket.show', $ticket->id), false)
            ->content();

        expect($html)->not->toContain('target="_blank"');
    });

    it('checkbox em /admin/settings vem pre-marcado para usuario sem preferencia salva', function () {
        actingAsAdmin();

        $html = $this->get(route('admin.settings'))
            ->assertOk()
            ->content();

        expect($html)->toMatch('/name="open_ticket_new_tab"[^>]*checked/s');
    });

    it('checkbox em /admin/settings vem desmarcado quando usuario desativou', function () {
        $admin = actingAsAdmin();

        Setting::create([
            'user_id' => $admin->id,
            'slug'    => 'open_ticket_new_tab',
            'value'   => '0',
            'default' => '0',
        ]);

        $html = $this->get(route('admin.settings'))
            ->assertOk()
            ->content();

        expect($html)->not->toMatch('/name="open_ticket_new_tab"[^>]*checked/s');
    });
});
