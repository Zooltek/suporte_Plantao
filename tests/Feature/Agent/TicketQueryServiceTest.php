<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

// ─── Helpers locais ───────────────────────────────────────────────────────────

/**
 * Cria um ticket via factory com atributos opcionais.
 */
function tqs_ticket(array $attrs = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'created_at' => now(),
        'updated_at' => now(),
    ], $attrs));
}

function tqs_category(string $name, array $attrs = []): Category
{
    return Category::factory()
        ->withDescription($name)
        ->create($attrs)
        ->refresh();
}

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('TicketQueryService — acesso e autenticação', function () {

    it('admin acessa a listagem com status 200', function () {
        actingAsAdmin();

        $this->get(route('agent.ticket.index'))->assertOk();
    });

    it('agente acessa a listagem com status 200', function () {
        actingAsAgent();

        $this->get(route('agent.ticket.index'))->assertOk();
    });

    it('usuário não autenticado é redirecionado', function () {
        $this->get(route('agent.ticket.index'))->assertRedirect();
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketQueryService — escopo por papel', function () {

    it('agente vê apenas seus próprios tickets capturados em Meus Chamados', function () {
        $agent = actingAsAgent();
        $other = User::factory()->agent()->create();

        tqs_ticket(['agent_id' => $agent->id, 'user_id' => $agent->id, 'subject' => 'Meu Chamado']);
        tqs_ticket(['agent_id' => $other->id, 'user_id' => $other->id, 'subject' => 'Chamado do Outro']);
        tqs_ticket(['agent_id' => null, 'user_id' => $agent->id, 'subject' => 'Chamado Sem Agente']);

        $response = $this->get(route('agent.ticket.index'));

        $response->assertOk()
            ->assertSee('Meu Chamado')
            ->assertDontSee('Chamado do Outro')
            ->assertDontSee('Chamado Sem Agente');
    });

    it('admin vê todos os tickets', function () {
        $agent = User::factory()->agent()->create();
        actingAsAdmin();

        tqs_ticket(['agent_id' => $agent->id, 'user_id' => $agent->id, 'subject' => 'Chamado Alpha']);
        tqs_ticket(['agent_id' => null, 'user_id' => $agent->id, 'subject' => 'Chamado Beta']);

        $response = $this->get(route('agent.ticket.index'));

        $response->assertOk()
            ->assertSee('Chamado Alpha')
            ->assertSee('Chamado Beta');
    });

    it('agente vê fila sem agente do próprio setor via unassigned e não vê de outro setor', function () {
        $support = Department::factory()->create(['name' => 'Suporte Técnico']);
        $finance = Department::factory()->create(['name' => 'Financeiro']);
        $agent = actingAsAgent(['department_id' => $support->id]);
        $financeAgent = User::factory()->agent()->create(['department_id' => $finance->id]);

        tqs_ticket([
            'agent_id' => null,
            'user_id' => $agent->id,
            'department_id' => $support->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'subject' => 'Fila Suporte WhatsApp',
        ]);
        tqs_ticket([
            'agent_id' => $financeAgent->id,
            'user_id' => $financeAgent->id,
            'department_id' => $finance->id,
            'subject' => 'Chamado Financeiro Restrito',
        ]);

        $this->get(route('agent.ticket.index', ['unassigned' => 1]))
            ->assertOk()
            ->assertSee('Fila Suporte WhatsApp')
            ->assertDontSee('Chamado Financeiro Restrito');
    });

    it('isola filas pendentes sem agente entre suporte e financeiro', function () {
        $support = Department::factory()->create(['name' => 'Suporte Técnico']);
        $finance = Department::factory()->create(['name' => 'Financeiro']);
        $supportAgent = actingAsAgent(['department_id' => $support->id]);
        $financeAgent = User::factory()->agent()->create(['department_id' => $finance->id]);

        tqs_ticket([
            'agent_id' => null,
            'user_id' => $supportAgent->id,
            'department_id' => $support->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'subject' => 'Fila Pendente Suporte',
        ]);

        tqs_ticket([
            'agent_id' => null,
            'user_id' => $financeAgent->id,
            'department_id' => $finance->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'subject' => 'Fila Pendente Financeiro',
        ]);

        $this->get(route('agent.ticket.index', ['unassigned' => 1]))
            ->assertOk()
            ->assertSee('Fila Pendente Suporte')
            ->assertDontSee('Fila Pendente Financeiro');

        $this->actingAs($financeAgent, 'admin');

        $this->get(route('agent.ticket.index', ['unassigned' => 1]))
            ->assertOk()
            ->assertSee('Fila Pendente Financeiro')
            ->assertDontSee('Fila Pendente Suporte');
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketQueryService — filtros', function () {

    it('filtro por status retorna apenas tickets daquele status', function () {
        $admin = actingAsAdmin();
        $status = Status::factory()->create(['name' => 'Em Análise']);

        tqs_ticket(['status_id' => $status->id, 'user_id' => $admin->id, 'subject' => 'Ticket Filtrado']);
        tqs_ticket(['status_id' => 999,          'user_id' => $admin->id, 'subject' => 'Ticket Outro Status']);

        $response = $this->get(route('agent.ticket.index', ['status' => $status->id]));

        $response->assertOk()
            ->assertSee('Ticket Filtrado')
            ->assertDontSee('Ticket Outro Status');
    });

    it('filtro por empresa retorna apenas tickets daquela empresa', function () {
        $admin = actingAsAdmin();
        $company = Company::factory()->create(['trade_name' => 'Empresa Alfa']);
        $other = Company::factory()->create(['trade_name' => 'Empresa Beta']);

        tqs_ticket(['company_id' => $company->id, 'user_id' => $admin->id, 'subject' => 'Ticket Alfa']);
        tqs_ticket(['company_id' => $other->id,   'user_id' => $admin->id, 'subject' => 'Ticket Beta']);

        $response = $this->get(route('agent.ticket.index', ['company' => $company->id]));

        $response->assertOk()
            ->assertSee('Ticket Alfa')
            ->assertDontSee('Ticket Beta');
    });

    it('filtro por agente retorna apenas tickets daquele agente', function () {
        $admin = actingAsAdmin();
        $agent1 = User::factory()->agent()->create();
        $agent2 = User::factory()->agent()->create();

        tqs_ticket(['agent_id' => $agent1->id, 'user_id' => $admin->id, 'subject' => 'Ticket Agente 1']);
        tqs_ticket(['agent_id' => $agent2->id, 'user_id' => $admin->id, 'subject' => 'Ticket Agente 2']);

        $response = $this->get(route('agent.ticket.index', ['agent' => $agent1->id]));

        $response->assertOk()
            ->assertSee('Ticket Agente 1')
            ->assertDontSee('Ticket Agente 2');
    });

    it('filtro por keyword busca no subject', function () {
        $admin = actingAsAdmin();

        tqs_ticket(['user_id' => $admin->id, 'subject' => 'Impressora sem tinta']);
        tqs_ticket(['user_id' => $admin->id, 'subject' => 'Problema de rede']);

        $response = $this->get(route('agent.ticket.index', ['q' => 'Impressora']));

        $response->assertOk()
            ->assertSee('Impressora sem tinta')
            ->assertDontSee('Problema de rede');
    });

    it('filtro por keyword busca no contato', function () {
        $admin = actingAsAdmin();

        tqs_ticket(['user_id' => $admin->id, 'subject' => 'Chamado X', 'contact' => 'MARCELO SILVA']);
        tqs_ticket(['user_id' => $admin->id, 'subject' => 'Chamado Y', 'contact' => 'JOAO PEREIRA']);

        $response = $this->get(route('agent.ticket.index', ['q' => 'MARCELO']));

        $response->assertOk()
            ->assertSee('Chamado X')
            ->assertDontSee('Chamado Y');
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketQueryService — ordenação', function () {

    it('order=2 (mais recentes) traz o mais novo primeiro e prioriza pendentes antes de finalizados', function () {
        $admin = actingAsAdmin();

        $statusPendente = Status::factory()->create(['name' => 'Pendente', 'is_terminal' => false]);
        $statusFinalizado = Status::factory()->create(['name' => 'Resolvido', 'is_terminal' => true]);

        tqs_ticket([
            'user_id' => $admin->id,
            'status_id' => $statusFinalizado->id,
            'subject' => 'Finalizado Recente',
            'created_at' => now()->subHour(),
        ]);
        tqs_ticket([
            'user_id' => $admin->id,
            'status_id' => $statusPendente->id,
            'subject' => 'Pendente Antigo',
            'created_at' => now()->subHours(5),
        ]);
        tqs_ticket([
            'user_id' => $admin->id,
            'status_id' => $statusPendente->id,
            'subject' => 'Pendente Recente',
            'created_at' => now()->subMinutes(10),
        ]);
        tqs_ticket([
            'user_id' => $admin->id,
            'status_id' => $statusFinalizado->id,
            'subject' => 'Finalizado Antigo',
            'created_at' => now()->subHours(10),
        ]);

        $response = $this->get(route('agent.ticket.index', ['order' => 2]));

        $content = $response->getContent();
        $posPendRecente = strpos($content, 'Pendente Recente');
        $posPendAntigo = strpos($content, 'Pendente Antigo');
        $posFinRecente = strpos($content, 'Finalizado Recente');
        $posFinAntigo = strpos($content, 'Finalizado Antigo');

        expect($posPendRecente)->toBeLessThan($posPendAntigo)
            ->and($posPendAntigo)->toBeLessThan($posFinRecente)
            ->and($posFinRecente)->toBeLessThan($posFinAntigo);
    });

    it('order=1 (última atualização) traz o mais recentemente atualizado primeiro', function () {
        $admin = actingAsAdmin();

        tqs_ticket([
            'user_id' => $admin->id,
            'subject' => 'Atualizado Hoje',
            'created_at' => now()->subDays(10),
            'updated_at' => now(),
        ]);
        tqs_ticket([
            'user_id' => $admin->id,
            'subject' => 'Atualizado Ontem',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->get(route('agent.ticket.index', ['order' => 1]));

        $content = $response->getContent();
        $posHoje = strpos($content, 'Atualizado Hoje');
        $posOntem = strpos($content, 'Atualizado Ontem');

        expect($posHoje)->toBeLessThan($posOntem);
    });

    it('default (prioridade+tempo) coloca ticket urgente antes do baixa prioridade', function () {
        $admin = actingAsAdmin();

        $catUrgente = Category::factory()->create(['parent_id' => 0, 'priority' => 'urgent']);
        $catBaixa = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        tqs_ticket([
            'user_id' => $admin->id,
            'subject' => 'Ticket Urgente',
            'category_id' => $catUrgente->category_id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        tqs_ticket([
            'user_id' => $admin->id,
            'subject' => 'Ticket Baixa',
            'category_id' => $catBaixa->category_id,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        // Sem parâmetro order → ordenação padrão (prioridade+tempo)
        $response = $this->get(route('agent.ticket.index'));

        $content = $response->getContent();
        $posUrgente = strpos($content, 'Ticket Urgente');
        $posBaixa = strpos($content, 'Ticket Baixa');

        expect($posUrgente)->toBeLessThan($posBaixa);
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketQueryService — filtro por período', function () {

    it('em Meus Chamados exibe por padrão apenas chamados criados na data atual (hoje)', function () {
        $agent = actingAsAgent();

        tqs_ticket([
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'subject' => 'Chamado de Hoje',
            'created_at' => now(),
        ]);
        tqs_ticket([
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'subject' => 'Chamado de Ontem',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('agent.ticket.index', ['mine' => 1]));

        $response->assertOk()
            ->assertSee('Chamado de Hoje')
            ->assertDontSee('Chamado de Ontem');
    });

    it('em Meus Chamados permite visualizar chamados anteriores ao informar filtro de período', function () {
        $agent = actingAsAgent();

        tqs_ticket([
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'subject' => 'Chamado Passado',
            'created_at' => now()->subDays(3),
        ]);
        tqs_ticket([
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'subject' => 'Chamado Muito Antigo',
            'created_at' => now()->subDays(30),
        ]);

        $response = $this->get(route('agent.ticket.index', [
            'mine' => 1,
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertSee('Chamado Passado')
            ->assertDontSee('Chamado Muito Antigo');
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketQueryService — getFilterData', function () {

    it('admin recebe lista de agentes nos filtros', function () {
        $admin = actingAsAdmin();
        User::factory()->agent()->create(['name' => 'Agente Visível']);

        $response = $this->get(route('agent.ticket.index'));

        $response->assertOk()->assertSee('Agente Visível');
    });

    it('view exibe opções de ordenação', function () {
        actingAsAdmin();

        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertSee('Prioridade + Tempo')
            ->assertSee('Última Atualização')
            ->assertSee('Mais Recentes');
    });

    it('filtro de categoria exibe rótulo humano em vez do header numérico', function () {
        actingAsAdmin();
        tqs_category('Financeiro', ['parent_id' => 0, 'header' => 1]);

        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertSee('Financeiro');
    });

    it('filtro de status expõe nomes deduplicados para o usuário final', function () {
        actingAsAdmin();
        Status::factory()->create(['name' => 'Em Andamento']);
        Status::factory()->create(['name' => ' em andamento ']);
        Status::factory()->create(['name' => 'Pendente']);

        $response = $this->get(route('agent.ticket.index'))->assertOk();

        $statusNames = collect($response->viewData('statuses'))
            ->map(fn (Status $status) => mb_strtolower(trim((string) $status->name)))
            ->values()
            ->all();

        expect($statusNames)->toContain('em andamento', 'pendente')
            ->and(array_count_values($statusNames)['em andamento'] ?? 0)->toBe(1);
    });

    it('listagem respeita paginação de 15 itens', function () {
        $admin = actingAsAdmin();

        for ($i = 1; $i <= 20; $i++) {
            tqs_ticket(['user_id' => $admin->id, 'subject' => "Ticket #{$i}"]);
        }

        $page1 = $this->get(route('agent.ticket.index'));
        $page2 = $this->get(route('agent.ticket.index', ['page' => 2]));

        $page1->assertOk()->assertSee('Ticket #1');
        $page2->assertOk();
    });

});

// ─────────────────────────────────────────────────────────────────────────────

describe('TicketQueryService — filtro mine', function () {

    it('admin com ?mine=1 vê apenas seus próprios tickets', function () {
        $admin = actingAsAdmin();
        $other = User::factory()->agent()->create();

        tqs_ticket(['agent_id' => $admin->id, 'subject' => 'Chamado do Admin', 'created_at' => now()]);
        tqs_ticket(['agent_id' => $other->id, 'subject' => 'Chamado do Outro', 'created_at' => now()]);

        $this->get(route('agent.ticket.index', ['mine' => 1]))
            ->assertOk()
            ->assertSee('Meus Chamados')
            ->assertSee('Em Meus Chamados são exibidos apenas os chamados capturados por você.')
            ->assertSee('Chamado do Admin')
            ->assertDontSee('Chamado do Outro');
    });

    it('admin sem ?mine vê todos os tickets', function () {
        $admin = actingAsAdmin();
        $other = User::factory()->agent()->create();

        tqs_ticket(['agent_id' => $admin->id, 'subject' => 'Chamado Alpha']);
        tqs_ticket(['agent_id' => $other->id, 'subject' => 'Chamado Beta']);

        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertSee('Chamado Alpha')
            ->assertSee('Chamado Beta');
    });

    it('agente com ?mine=1 vê apenas seus tickets (comportamento idêntico ao padrão)', function () {
        $agent = actingAsAgent();
        $other = User::factory()->agent()->create();

        tqs_ticket(['agent_id' => $agent->id, 'subject' => 'Meu Chamado']);
        tqs_ticket(['agent_id' => $other->id, 'subject' => 'Chamado Alheio']);

        $this->get(route('agent.ticket.index', ['mine' => 1]))
            ->assertOk()
            ->assertSee('Meu Chamado')
            ->assertDontSee('Chamado Alheio');
    });

    it('modo meus chamados preserva o escopo ao limpar filtros', function () {
        actingAsAdmin();

        $response = $this->get(route('agent.ticket.index', ['mine' => 1, 'q' => 'ERP']));

        $response->assertOk()
            ->assertSee(route('agent.ticket.index', ['mine' => 1]), false);
    });

    it('modo sem agente filtra apenas tickets sem responsável e preserva escopo ao limpar filtros', function () {
        $admin = actingAsAdmin();
        $other = User::factory()->agent()->create();

        tqs_ticket(['agent_id' => $admin->id, 'subject' => 'Chamado Atribuido Admin']);
        tqs_ticket(['agent_id' => $other->id, 'subject' => 'Chamado Atribuido Outro']);
        tqs_ticket(['agent_id' => null, 'subject' => 'Chamado Fila Livre']);

        $response = $this->get(route('agent.ticket.index', ['unassigned' => 1]));

        $response->assertOk()
            ->assertSee('Chamados Sem Agente (Fila)')
            ->assertSee('Chamado Fila Livre')
            ->assertDontSee('Chamado Atribuido Admin')
            ->assertDontSee('Chamado Atribuido Outro')
            ->assertSee(route('agent.ticket.index', ['unassigned' => 1]), false);
    });

    it('criação real persiste category_id canônico e mostra nomes humanos na listagem', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create([
            'requires_agent' => false,
            'requires_schedule' => false,
            'requires_solution' => false,
            'is_terminal' => false,
        ]);
        $parent = tqs_category('Financeiro', ['parent_id' => 0, 'header' => 1]);
        $child = tqs_category('Boleto', ['parent_id' => $parent->category_id, 'header' => 1]);

        $this->post(route('agent.ticket.store'), [
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $child->category_id,
            'contact' => 'ANA TESTE',
            'agent_id' => $agent->id,
            'trouble' => 'Falha ao gerar boleto',
        ])->assertRedirect();

        $ticket = Ticket::query()->latest('id')->first();

        expect($ticket)->not->toBeNull()
            ->and((int) $ticket->category_id)->toBe($parent->category_id);

        $this->get(route('agent.ticket.index', ['mine' => 1]))
            ->assertOk()
            ->assertSee('Financeiro')
            ->assertSee('Boleto')
            ->assertDontSee('Sem categoria');
    });

    it('filtro por categoria usa a chave canônica de solutions_category', function () {
        $admin = actingAsAdmin();
        $finance = tqs_category('Financeiro', ['parent_id' => 0]);
        $support = tqs_category('Suporte', ['parent_id' => 0]);

        tqs_ticket([
            'user_id' => $admin->id,
            'category_id' => $finance->category_id,
            'subject' => 'Ticket Financeiro',
        ]);
        tqs_ticket([
            'user_id' => $admin->id,
            'category_id' => $support->category_id,
            'subject' => 'Ticket Suporte',
        ]);

        $this->get(route('agent.ticket.index', ['category' => $finance->category_id]))
            ->assertOk()
            ->assertSee('Ticket Financeiro')
            ->assertDontSee('Ticket Suporte');
    });

});
