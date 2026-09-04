<?php

use App\Exceptions\TicketBusinessException;
use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Agent\TicketService;

// ─── Helpers locais ───────────────────────────────────────────────────────────

/**
 * Cria par (categoria-pai, subcategoria) válido no banco.
 */
function ts_cat_pair(string $parentPrio = 'low', string $subPrio = 'low'): array
{
    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => $parentPrio]);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => $subPrio]);

    return [$cat, $sub];
}

/**
 * Retorna array $data mínimo válido para TicketService::saveTicket.
 */
function ts_data(Company $company, Category $cat, Category $sub, array $override = []): array
{
    return array_merge([
        'company_id' => $company->id,
        'status_id' => Ticket::STATUS_PENDING_ID,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
        'contact' => 'JOAO SILVA',
        'agent_id' => null,
        'is_recurring' => false,
    ], $override);
}

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('TicketService — saveTicket — regra de agente/status', function () {

    it('sem agente força status Pendente independente do status enviado', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => null, 'status_id' => 1]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->status_id)->toBe(Ticket::STATUS_PENDING_ID)
            ->and($ticket->agent_id)->toBeNull();
    });

    it('com agente e status não terminal força Em Andamento (2)', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        // status_id=1 não é terminal (3=Resolvido, 5=Não Resolvido) → deve virar 2
        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => 1]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->status_id)->toBe(2)
            ->and($ticket->agent_id)->toBe($user->id);
    });

    it('com agente e status terminal preserva o status e seta completed_at', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::factory()->terminal()->create();
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => $terminal->id]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->status_id)->toBe($terminal->id)
            ->and($ticket->completed_at)->not->toBeNull();
    });

    it('com agente e segundo status terminal também preserva o status', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::factory()->terminal()->create();
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => $terminal->id]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->status_id)->toBe($terminal->id);
    });

    it('status não-terminal com agente mantém completed_at nulo', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => 1]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->completed_at)->toBeNull();
    });

    it('novo ticket recebe author_id do usuário logado', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->author_id)->toBe($user->id);
    });

    it('is_recurring é armazenado corretamente', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['is_recurring' => true]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect((bool) $ticket->is_recurring)->toBeTrue();
    });

    it('contact é armazenado em maiúsculas', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['contact' => 'maria jose']);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->contact)->toBe('MARIA JOSE');
    });

    it('atualiza ticket existente sem sobrescrever author_id', function () {
        $user = User::factory()->agent()->create();
        $author = User::factory()->create();
        test()->actingAs($user, 'admin');

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $existing = Ticket::factory()->create([
            'author_id' => $author->id,
            'company_id' => $company->id,
            'category_id' => $cat->category_id,
        ]);

        $data = ts_data($company, $cat, $sub);
        $ticket = app(TicketService::class)->saveTicket($existing, $data, $user);

        expect($ticket->author_id)->toBe($author->id);
    });

    it('com agente e status que requer agendamento preserva o status (Visita Técnica)', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::factory()->visitaTecnica()->create();
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => $vt->id]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->status_id)->toBe($vt->id)
            ->and($ticket->agent_id)->toBe($user->id);
    });

    it('status que requer agendamento não seta completed_at (não é terminal)', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $vt = Status::factory()->visitaTecnica()->create();
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => $vt->id]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->completed_at)->toBeNull();
    });

    it('status comum não-terminal com agente continua sendo forçado para Em Andamento', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $comum = Status::factory()->create(); // is_terminal=false, requires_schedule=false
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => $user->id, 'status_id' => $comum->id]);
        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        expect($ticket->status_id)->toBe(2);
    });

    it('extra_categories são sincronizadas (apaga e recria)', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();
        [$cat2, $sub2] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, [
            'extra_categories' => [
                ['category_id' => $cat2->category_id, 'sub_category_id' => $sub2->category_id],
            ],
        ]);

        $ticket = app(TicketService::class)->saveTicket(new Ticket, $data, $user);

        $this->assertDatabaseHas('ticket_extra_categories', [
            'ticket_id' => $ticket->id,
            'category_id' => $cat2->category_id,
            'sub_category_id' => $sub2->category_id,
        ]);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketService — validateBusinessRules', function () {

    it('status terminal sem agente lança TicketBusinessException', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $terminal = Status::factory()->terminal()->create();
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub, ['agent_id' => null, 'status_id' => $terminal->id]);

        expect(fn () => app(TicketService::class)->saveTicket(new Ticket, $data, $user))
            ->toThrow(TicketBusinessException::class, 'exige um agente atribuído');
    });

    it('subcategoria com parent_id incorreto lança TicketBusinessException', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);

        $cat = Category::factory()->create(['parent_id' => 0]);
        $sub = Category::factory()->create(['parent_id' => 0]); // não é filha de $cat

        $data = ts_data($company, $cat, $sub);

        expect(fn () => app(TicketService::class)->saveTicket(new Ticket, $data, $user))
            ->toThrow(TicketBusinessException::class, 'inválido');
    });

    it('categoria inexistente lança TicketBusinessException', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [, $sub] = ts_cat_pair();

        $data = ts_data($company, new Category(['category_id' => 99999]), $sub);
        $data['category_id'] = 99999;

        expect(fn () => app(TicketService::class)->saveTicket(new Ticket, $data, $user))
            ->toThrow(TicketBusinessException::class);
    });

    it('empresa inativa lança TicketBusinessException', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => false]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub);

        expect(fn () => app(TicketService::class)->saveTicket(new Ticket, $data, $user))
            ->toThrow(TicketBusinessException::class, 'inativo');
    });

    it('empresa inadimplente bloqueia criação de novo ticket', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => true,
            'observations' => 'Boleto vencido',
        ]);
        [$cat, $sub] = ts_cat_pair();

        $data = ts_data($company, $cat, $sub);

        expect(fn () => app(TicketService::class)->saveTicket(new Ticket, $data, $user))
            ->toThrow(TicketBusinessException::class, 'inadimplência');
    });

    it('empresa inadimplente permite atualização de ticket existente', function () {
        $user = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => true]);
        [$cat, $sub] = ts_cat_pair();

        $id = \Illuminate\Support\Facades\DB::table('ticketit')->insertGetId([
            'subject' => 'Existente',
            'content' => 'Conteúdo',
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => 1,
            'contact' => 'ORIGINAL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $existing = Ticket::find($id);

        $data = ts_data($company, $cat, $sub);

        // Não deve lançar exceção (ticket já existe)
        $ticket = app(TicketService::class)->saveTicket($existing, $data, $user);

        expect($ticket->id)->toBe($existing->id);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketService — requestElapsedToken', function () {

    it('gera token de 64 caracteres e persiste no ticket', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub),
            $user
        );

        $token = app(TicketService::class)->requestElapsedToken($ticket);

        expect($token)->toHaveLength(64)
            ->and($token)->toMatch('/^[a-f0-9]{64}$/');

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'elapsed_token' => $token,
        ]);
    });

    it('cada chamada gera token diferente', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub),
            $user
        );

        $t1 = app(TicketService::class)->requestElapsedToken($ticket);
        $t2 = app(TicketService::class)->requestElapsedToken($ticket->fresh());

        expect($t1)->not->toBe($t2);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketService — parseVersion (via saveTicket)', function () {

    it('version null gera string vazia', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub),  // sem version → null
            $user
        );

        expect($ticket->version)->toBe('');
    });

    it('version numérica 1 → 01.28.01', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub, ['version' => '1']),
            $user
        );

        expect($ticket->version)->toBe('01.28.01');
    });

    it('version numérica 2 → 01.30.01', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub, ['version' => '2']),
            $user
        );

        expect($ticket->version)->toBe('01.30.01');
    });

    it('version numérica 3 → 01.32.01', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub, ['version' => '3']),
            $user
        );

        expect($ticket->version)->toBe('01.32.01');
    });

    it('version já formatada (dd.dd.dd) é mantida', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub, ['version' => '02.01.05']),
            $user
        );

        expect($ticket->version)->toBe('02.01.05');
    });

    it('version desconhecida (outro número) retorna string vazia', function () {
        $user = User::factory()->agent()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = ts_cat_pair();

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            ts_data($company, $cat, $sub, ['version' => '99']),
            $user
        );

        expect($ticket->version)->toBe('');
    });

});
