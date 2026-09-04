<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

/**
 * Proteção contra IDOR (OWASP A01) — garante que agentes não acessam
 * recursos de outros usuários via manipulação de ID na URL.
 */
describe('IDOR — Ticket update', function () {

    beforeEach(function () {
        $this->company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $this->status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $this->parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    });

    it('agente não consegue editar ticket de outro agente (GET edit retorna 403)', function () {
        $owner = User::factory()->agent()->create();
        $attacker = actingAsAgent();

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'agent_id' => $owner->id,
            'user_id' => $owner->id,
        ]);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertForbidden();
    });

    it('agente não consegue atualizar ticket de outro agente (PUT update retorna 403)', function () {
        $owner = User::factory()->agent()->create();
        $attacker = actingAsAgent();

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'agent_id' => $owner->id,
            'user_id' => $owner->id,
        ]);

        $this->put(route('agent.ticket.update', $ticket->id), [
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'subject' => 'Tentativa de IDOR',
        ])->assertForbidden();
    });

    it('admin consegue editar qualquer ticket', function () {
        $admin = actingAsAdmin();

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'agent_id' => $admin->id,
            'user_id' => $admin->id,
        ]);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertOk();
    });

    it('agente responsável consegue editar o próprio ticket', function () {
        $agent = actingAsAgent();

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status_id' => $this->status->id,
            'category_id' => $this->parent->category_id,
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
        ]);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertOk();
    });
});

describe('IDOR — KnowledgeArticle delete', function () {

    it('agente não consegue deletar artigo de outro autor (DELETE retorna 403)', function () {
        $author = User::factory()->agent()->create();
        $attacker = actingAsAgent();

        $article = KnowledgeArticle::factory()->create(['author_id' => $author->id]);

        $this->delete(route('agent.knowledge.destroy', $article->id))
            ->assertForbidden();
    });

    it('autor consegue deletar o próprio artigo', function () {
        $author = actingAsAgent();

        $article = KnowledgeArticle::factory()->create(['author_id' => $author->id]);

        $this->delete(route('agent.knowledge.destroy', $article->id))
            ->assertRedirect(route('agent.knowledge.index'));
    });

    it('admin consegue deletar qualquer artigo', function () {
        $author = User::factory()->agent()->create();
        $admin = actingAsAdmin();

        $article = KnowledgeArticle::factory()->create(['author_id' => $author->id]);

        $this->delete(route('agent.knowledge.destroy', $article->id))
            ->assertRedirect(route('agent.knowledge.index'));
    });
});
