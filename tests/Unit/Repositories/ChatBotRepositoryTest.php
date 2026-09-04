<?php

/**
 * Testes de integração do ChatBotRepository — SQLite in-memory.
 *
 * Cobrem todos os métodos públicos da implementação, validando que as queries
 * produzem os resultados esperados sem acionar lógica de negócio do Service.
 */

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Repositories\ChatBotRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cb_repo(): ChatBotRepository
{
    return new ChatBotRepository;
}

function cb_company(array $attrs = []): Company
{
    return Company::factory()->create($attrs);
}

function cb_user(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

function cb_ticket(array $attrs = []): Ticket
{
    $cat = Category::factory()->create(['parent_id' => 0]);

    return Ticket::factory()->create(array_merge([
        'category_id' => $cat->category_id,
    ], $attrs));
}

// ─── findCompanyByName ────────────────────────────────────────────────────────

describe('ChatBotRepository — findCompanyByName', function () {

    it('encontra empresa por nome parcial', function () {
        $company = cb_company(['name' => 'Amura Tech LTDA', 'trade_name' => 'Amura']);

        $found = cb_repo()->findCompanyByName('Amura Tech');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id);
    });

    it('encontra empresa por trade_name parcial', function () {
        $company = cb_company(['name' => 'Razão Social LTDA', 'trade_name' => 'Fantasia Comercial']);

        $found = cb_repo()->findCompanyByName('Fantasia');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id);
    });

    it('retorna null quando empresa não é encontrada', function () {
        $found = cb_repo()->findCompanyByName('EmpresaInexistenteXYZ999');

        expect($found)->toBeNull();
    });

    it('não diferencia maiúsculas de minúsculas na busca', function () {
        $company = cb_company(['name' => 'TechCorp Solutions']);

        $found = cb_repo()->findCompanyByName('techcorp');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id);
    });

});

// ─── findCompanyByCnpj ────────────────────────────────────────────────────────

describe('ChatBotRepository — findCompanyByCnpj', function () {

    it('encontra empresa cadastrada com CNPJ formatado ao receber apenas dígitos', function () {
        $company = cb_company(['cnpj' => '36.423.135/0001-07']);

        $found = cb_repo()->findCompanyByCnpj('36423135000107');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id);
    });

    it('encontra empresa cadastrada apenas com dígitos ao receber CNPJ formatado', function () {
        $company = cb_company(['cnpj' => '36423135000107']);

        $found = cb_repo()->findCompanyByCnpj('36.423.135/0001-07');

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($company->id);
    });

    it('retorna null quando CNPJ não existe na base', function () {
        cb_company(['cnpj' => '11.222.333/0001-81']);

        $found = cb_repo()->findCompanyByCnpj('99999999000199');

        expect($found)->toBeNull();
    });

    it('retorna null quando CNPJ informado é vazio', function () {
        cb_company(['cnpj' => '11.222.333/0001-81']);

        expect(cb_repo()->findCompanyByCnpj(''))->toBeNull();
    });

});

// ─── findLastTicketByCompanyId ─────────────────────────────────────────────────

describe('ChatBotRepository — findLastTicketByCompanyId', function () {

    it('retorna o ticket mais recente da empresa', function () {
        $company = cb_company();

        $older = cb_ticket(['company_id' => $company->id, 'created_at' => now()->subDays(2)]);
        $newer = cb_ticket(['company_id' => $company->id, 'created_at' => now()->subDay()]);

        $found = cb_repo()->findLastTicketByCompanyId($company->id);

        expect($found->id)->toBe($newer->id);
    });

    it('retorna null quando empresa não tem tickets', function () {
        $company = cb_company();

        $found = cb_repo()->findLastTicketByCompanyId($company->id);

        expect($found)->toBeNull();
    });

    it('carrega o relacionamento status (eager loading sem N+1)', function () {
        $company = cb_company();
        cb_ticket(['company_id' => $company->id]);

        $found = cb_repo()->findLastTicketByCompanyId($company->id);

        expect($found->relationLoaded('status'))->toBeTrue();
    });

    it('não retorna tickets de outra empresa', function () {
        $company1 = cb_company();
        $company2 = cb_company();

        cb_ticket(['company_id' => $company1->id]);

        $found = cb_repo()->findLastTicketByCompanyId($company2->id);

        expect($found)->toBeNull();
    });

});

// ─── findLastTicketByPhone ────────────────────────────────────────────────────

describe('ChatBotRepository — findLastTicketByPhone', function () {

    it('retorna o ticket que contém o telefone no campo obs', function () {
        $phone = '5527999990000';
        $ticket = cb_ticket(['obs' => "Contato via WhatsApp: {$phone} - urgente"]);

        $found = cb_repo()->findLastTicketByPhone($phone);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($ticket->id);
    });

    it('retorna o ticket mais recente que contém o telefone', function () {
        $phone = '5527888880000';

        $older = cb_ticket(['obs' => $phone, 'created_at' => now()->subDays(2)]);
        $newer = cb_ticket(['obs' => $phone, 'created_at' => now()->subDay()]);

        $found = cb_repo()->findLastTicketByPhone($phone);

        expect($found->id)->toBe($newer->id);
    });

    it('retorna null quando nenhum ticket contém o telefone no obs', function () {
        $found = cb_repo()->findLastTicketByPhone('5527000000000');

        expect($found)->toBeNull();
    });

    it('carrega o relacionamento status (eager loading sem N+1)', function () {
        $phone = '5527777770000';
        cb_ticket(['obs' => $phone]);

        $found = cb_repo()->findLastTicketByPhone($phone);

        expect($found->relationLoaded('status'))->toBeTrue();
    });

});

// ─── getAgentsAndAdmins ────────────────────────────────────────────────────────

describe('ChatBotRepository — getAgentsAndAdmins', function () {

    it('retorna usuários com ticketit_agent = 1', function () {
        $agent = cb_user(['ticketit_agent' => 1, 'ticketit_admin' => 0]);
        cb_user(['ticketit_agent' => 0, 'ticketit_admin' => 0]); // usuário comum

        $result = cb_repo()->getAgentsAndAdmins();

        expect($result->pluck('id'))->toContain($agent->id);
    });

    it('retorna usuários com ticketit_admin = 1', function () {
        $admin = cb_user(['ticketit_agent' => 0, 'ticketit_admin' => 1]);
        cb_user(['ticketit_agent' => 0, 'ticketit_admin' => 0]); // usuário comum

        $result = cb_repo()->getAgentsAndAdmins();

        expect($result->pluck('id'))->toContain($admin->id);
    });

    it('não retorna usuários sem permissão de agente ou admin', function () {
        $normal = cb_user(['ticketit_agent' => 0, 'ticketit_admin' => 0]);

        $result = cb_repo()->getAgentsAndAdmins();

        expect($result->pluck('id'))->not->toContain($normal->id);
    });

    it('retorna coleção vazia quando não há agentes nem admins', function () {
        // Sem criar nenhum agente/admin
        $result = cb_repo()->getAgentsAndAdmins();

        expect($result)->toBeEmpty();
    });

    it('retorna usuário que é agente e admin ao mesmo tempo sem duplicata', function () {
        $both = cb_user(['ticketit_agent' => 1, 'ticketit_admin' => 1]);

        $result = cb_repo()->getAgentsAndAdmins();

        expect($result->where('id', $both->id))->toHaveCount(1);
    });

});

// ─── saveConversation ─────────────────────────────────────────────────────────

describe('ChatBotRepository — saveConversation', function () {

    it('persiste alterações na conversa', function () {
        $conv = WhatsAppConversation::factory()->awaitingName()->create();
        $conv->mergePayload(['name' => 'Teste']);

        cb_repo()->saveConversation($conv);

        $fresh = $conv->fresh();
        expect($fresh->getPayloadValue('name'))->toBe('Teste');
    });

});
