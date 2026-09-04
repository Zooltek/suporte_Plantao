<?php

use App\Models\User;
use App\Repositories\AgentRepository;
use Carbon\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ar_agent(array $attrs = []): User
{
    return User::factory()->agent()->create(array_merge([
        'active'         => true,
        'ticketit_agent' => 1,
        'department_id'  => 1,
    ], $attrs));
}

// ─── listAgents ───────────────────────────────────────────────────────────────

describe('AgentRepository — listAgents', function () {

    it('retorna apenas ticketit_agent=1 quando showAll=false', function () {
        $agent    = ar_agent(['ticketit_agent' => 1]);
        $nonAgent = ar_agent(['ticketit_agent' => 0]);

        $result = (new AgentRepository())->listAgents(false);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($agent->id)
            ->and($ids)->not->toContain($nonAgent->id);
    });

    it('retorna todos quando showAll=true incluindo não agentes', function () {
        $agent    = ar_agent(['ticketit_agent' => 1]);
        $nonAgent = ar_agent(['ticketit_agent' => 0]);

        $result = (new AgentRepository())->listAgents(true);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($agent->id)
            ->and($ids)->toContain($nonAgent->id);
    });

    it('carrega a relação department em cada agente', function () {
        ar_agent();

        $result = (new AgentRepository())->listAgents(false);

        expect($result->first()->relationLoaded('department'))->toBeTrue();
    });

    it('retorna a coluna last_tickets_count', function () {
        ar_agent();

        $result = (new AgentRepository())->listAgents(false);

        expect($result->first())->toHaveKey('last_tickets_count')
            ->and($result->first()->last_tickets_count)->toBeInt();
    });

    it('retorna coleção vazia quando nenhum agente ativo satisfaz os critérios', function () {
        ar_agent(['active' => false, 'ticketit_agent' => 1]);

        $result = (new AgentRepository())->listAgents(false);

        expect($result->filter(fn ($a) => !$a->active))->toBeEmpty();
    });

});

// ─── getAgentRatings ──────────────────────────────────────────────────────────

describe('AgentRepository — getAgentRatings', function () {

    it('retorna apenas usuários do departamento 1', function () {
        $dep1 = ar_agent(['department_id' => 1]);
        $dep2 = ar_agent(['department_id' => 2]);

        $result = (new AgentRepository())->getAgentRatings();
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($dep1->id)
            ->and($ids)->not->toContain($dep2->id);
    });

    it('retorna a coluna rating_count', function () {
        ar_agent(['department_id' => 1]);

        $result = (new AgentRepository())->getAgentRatings();

        expect($result->first())->toHaveKey('rating_count');
    });

    it('rating_avg é null quando não há avaliações', function () {
        ar_agent(['department_id' => 1]);

        $result = (new AgentRepository())->getAgentRatings();

        expect($result->first()->rating_avg)->toBeNull();
    });

});

// ─── getBirthdays ─────────────────────────────────────────────────────────────

describe('AgentRepository — getBirthdays', function () {

    it('retorna agentes que fazem aniversário hoje', function () {
        $today = Carbon::now();
        $agent = ar_agent(['birthday' => $today->format('Y-m-d')]);

        $result = (new AgentRepository())->getBirthdays(0);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($agent->id);
    });

    it('exclui o usuário cujo id é passado', function () {
        $today  = Carbon::now();
        $agent  = ar_agent(['birthday' => $today->format('Y-m-d')]);

        $result = (new AgentRepository())->getBirthdays($agent->id);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->not->toContain($agent->id);
    });

    it('não inclui agente cujo aniversário é daqui a 30 dias', function () {
        $future = Carbon::now()->addDays(30);
        $agent  = ar_agent(['birthday' => $future->format('Y-m-d')]);

        // Só inclui se dia+mês coincide com hoje
        $today = Carbon::now();
        if ($future->format('m-d') !== $today->format('m-d')) {
            $result = (new AgentRepository())->getBirthdays(0);
            $ids    = $result->pluck('id')->toArray();

            expect($ids)->not->toContain($agent->id);
        } else {
            expect(true)->toBeTrue(); // edge-case: coincidência de data, skip
        }
    });

    it('retorna somente aniversariantes do dia e exclui o id informado', function () {
        $today  = Carbon::now();
        $agentA = ar_agent(['birthday' => $today->format('Y-m-d')]);
        $agentB = ar_agent(['birthday' => $today->format('Y-m-d')]);

        $result = (new AgentRepository())->getBirthdays($agentA->id);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($agentB->id)
            ->and($ids)->not->toContain($agentA->id);
    });

});
