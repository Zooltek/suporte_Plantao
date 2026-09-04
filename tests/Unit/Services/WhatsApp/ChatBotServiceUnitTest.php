<?php

/**
 * Testes UNITÁRIOS do ChatBotService — Repositório mockado via interface.
 *
 * Estes testes isolam as regras de negócio do Service sem tocar o banco de dados.
 * O ChatBotRepositoryInterface é mockado para controlar retornos e verificar
 * que o Service delega as consultas corretamente.
 */

use App\Contracts\Repositories\ChatBotRepositoryInterface;
use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\ChatBotService;
use App\Services\WhatsApp\CompanyPhoneLookupService;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppTicketService;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cbs_msg(string $body, string $from = '5527999990000'): IncomingMessage
{
    return new IncomingMessage($from, $body, 'text', null, null, null, (string) time(), uniqid());
}

function cbs_service(): array
{
    $whatsApp = Mockery::mock(WhatsAppService::class);
    $tickets = Mockery::mock(WhatsAppTicketService::class);
    $chatBotRepo = Mockery::mock(ChatBotRepositoryInterface::class);
    $companyPhoneLookup = Mockery::mock(CompanyPhoneLookupService::class);

    $service = new ChatBotService($whatsApp, $tickets, $chatBotRepo, $companyPhoneLookup);

    // saveConversation delega para o save() real do modelo para que o estado
    // persista no banco (SQLite in-memory) e fresh() reflita a mudança.
    $whatsApp->shouldReceive('send')->byDefault();
    $chatBotRepo->shouldReceive('saveConversation')->byDefault()
        ->andReturnUsing(fn ($conv) => $conv->save());
    $chatBotRepo->shouldReceive('getAgentsAndAdmins')->byDefault()
        ->andReturn(new \Illuminate\Database\Eloquent\Collection);
    $companyPhoneLookup->shouldReceive('resolve')->byDefault()->andReturn(null);

    return [$service, $whatsApp, $tickets, $chatBotRepo, $companyPhoneLookup];
}

// ─── Delegação ao Repository ──────────────────────────────────────────────────

describe('ChatBotService — delega findCompanyByName ao repository', function () {

    it('chama findCompanyByName com o nome informado', function () {
        [$service, $whatsApp, , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $repo->shouldReceive('findCompanyByName')
            ->once()
            ->with('Amura Tech')
            ->andReturn(null);

        $service->handle($conv, cbs_msg('Amura Tech'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

    it('vincula company_id quando repository retorna empresa', function () {
        [$service, $whatsApp, , $repo] = cbs_service();

        $company = new Company;
        $company->id = 42;

        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $repo->shouldReceive('findCompanyByName')->andReturn($company);

        $service->handle($conv, cbs_msg('Alguma Empresa'));

        expect($conv->fresh()->company_id)->toBe(42);
    });

    it('não vincula company_id quando repository retorna null', function () {
        [$service, $whatsApp, , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $repo->shouldReceive('findCompanyByName')->andReturn(null);

        $service->handle($conv, cbs_msg('Empresa Fictícia'));

        $conv->refresh();
        expect($conv->company_id)->toBeNull()
            ->and($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ)
            ->and($conv->getPayloadValue('company_name_attempted'))->toBe('Empresa Fictícia');
    });

});

// ─── queryTicketStatus — delegação ao repository ──────────────────────────────

describe('ChatBotService — queryTicketStatus delega ao repository', function () {

    it('chama findLastTicketByCompanyId quando conversa tem company_id', function () {
        [$service, , , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->awaitingMenu()->create([
            'company_id' => 99,
        ]);

        $repo->shouldReceive('findLastTicketByCompanyId')
            ->once()
            ->with(99)
            ->andReturn(null);

        $repo->shouldReceive('findLastTicketByPhone')->andReturn(null);

        $service->handle($conv, cbs_msg('2'));
    });

    it('chama findLastTicketByPhone quando não há company_id', function () {
        [$service, , , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->awaitingMenu()->create([
            'company_id' => null,
        ]);

        $repo->shouldReceive('findLastTicketByCompanyId')->never();
        $repo->shouldReceive('findLastTicketByPhone')
            ->once()
            ->with($conv->phone)
            ->andReturn(null);

        $service->handle($conv, cbs_msg('2'));
    });

    it('exibe status do ticket encontrado via repository', function () {
        [$service, $whatsApp, , $repo] = cbs_service();

        $status = new \App\Models\Ticket\Status;
        $status->name = 'Em Atendimento';

        $ticket = Mockery::mock(Ticket::class)->makePartial();
        $ticket->id = 77;
        $ticket->subject = 'Sistema travando';
        $ticket->shouldReceive('getAttribute')->with('status')->andReturn($status);
        $ticket->created_at = now();

        $conv = WhatsAppConversation::factory()->awaitingMenu()->create([
            'company_id' => null,
        ]);

        $repo->shouldReceive('findLastTicketByPhone')
            ->andReturn($ticket);

        $whatsApp->shouldReceive('send')
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, '77') && str_contains($msg, 'Em Atendimento')))
            ->once();

        $service->handle($conv, cbs_msg('2'));
    });

});

// ─── handover — delega getAgentsAndAdmins ao repository ──────────────────────

describe('ChatBotService — handover delega getAgentsAndAdmins ao repository', function () {

    it('chama getAgentsAndAdmins uma vez durante o handover', function () {
        [$service, , , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $repo->shouldReceive('getAgentsAndAdmins')
            ->once()
            ->andReturn(new \Illuminate\Database\Eloquent\Collection);

        $service->handle($conv, cbs_msg('3'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('notifica cada agente retornado pelo repository', function () {
        [$service, , , $repo] = cbs_service();

        // Cria usuários reais no banco para FK de Notification::store()
        $agent1 = \App\Models\User::factory()->create(['ticketit_agent' => 1]);
        $agent2 = \App\Models\User::factory()->create(['ticketit_agent' => 1]);

        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $repo->shouldReceive('getAgentsAndAdmins')
            ->andReturn(new \Illuminate\Database\Eloquent\Collection([$agent1, $agent2]));

        $service->handle($conv, cbs_msg('3'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
        $this->assertDatabaseCount('user_notifications', 2);
    });

});

// ─── saveConversation — chamado pelo repository em transições ─────────────────

describe('ChatBotService — saveConversation é delegado ao repository', function () {

    it('chama saveConversation durante a transição de estado', function () {
        [$service, , , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->greeting()->create();

        $repo->shouldReceive('saveConversation')
            ->atLeast()->once()
            ->with($conv);

        $service->handle($conv, cbs_msg('oi'));
    });

    it('chama saveConversation no restart da conversa', function () {
        [$service, , , $repo] = cbs_service();

        $conv = WhatsAppConversation::factory()->completed()->create([
            'payload' => ['name' => 'Antigo'],
        ]);

        $repo->shouldReceive('saveConversation')
            ->once()
            ->with($conv);

        $service->handle($conv, cbs_msg('nova mensagem'));
    });

});
