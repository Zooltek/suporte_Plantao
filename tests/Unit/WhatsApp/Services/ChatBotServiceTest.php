<?php

use App\Contracts\Repositories\ChatBotRepositoryInterface;
use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Models\Company;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\ChatBotService;
use App\Services\WhatsApp\CompanyPhoneLookupService;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppTicketService;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Carbon;

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function textMsg(string $body, string $from = '5527999990000'): IncomingMessage
{
    return new IncomingMessage($from, $body, 'text', null, null, null, (string) time(), uniqid());
}

function mediaMsg(string $type = 'image'): IncomingMessage
{
    return new IncomingMessage('5527999990000', '', $type, 'https://img.url', 'media-id', 'image/jpeg', (string) time(), uniqid());
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-05-05 09:00:00'));

    $this->whatsApp   = Mockery::mock(WhatsAppService::class);
    $this->tickets    = Mockery::mock(WhatsAppTicketService::class);
    $this->chatBotRepo = Mockery::mock(ChatBotRepositoryInterface::class);
    $this->companyPhoneLookup = Mockery::mock(CompanyPhoneLookupService::class);
    $this->chatBot    = new ChatBotService($this->whatsApp, $this->tickets, $this->chatBotRepo, $this->companyPhoneLookup);

    // Por padrão, silencia todas as chamadas de send().
    // saveConversation delega para o save() real do modelo para que o estado
    // persista no banco (SQLite in-memory) e $conv->fresh() reflita a mudança.
    $this->whatsApp->shouldReceive('send')->byDefault();
    $this->chatBotRepo->shouldReceive('saveConversation')->byDefault()
        ->andReturnUsing(fn ($conv) => $conv->save());
    $this->chatBotRepo->shouldReceive('getAgentsAndAdmins')->byDefault()
        ->andReturn(new \Illuminate\Database\Eloquent\Collection());
    $this->companyPhoneLookup->shouldReceive('resolve')->byDefault()->andReturn(null);
});

afterEach(function () {
    Carbon::setTestNow();
});

// ─────────────────────────────────────────────────────────────────────────────
// Cancelamento universal
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — cancelamento universal', function () {

    it('cancela com palavra "cancelar" em qualquer estado', function (string $keyword) {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.cancelled'));

        $this->chatBot->handle($conv, textMsg($keyword));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    })->with(['cancelar', 'cancel', 'sair', 'exit']);

});

// ─────────────────────────────────────────────────────────────────────────────
// Sessão expirada
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — sessão expirada', function () {

    it('reinicia conversa expirada', function () {
        $conv = WhatsAppConversation::factory()->expired()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.greeting_identified'));

        $this->chatBot->handle($conv, textMsg('oi'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ)
            ->and($conv->payload)->toBe([]);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: GREETING
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — GREETING', function () {

    it('envia saudação e transita para AWAITING_COMPANY_CNPJ', function () {
        $conv = WhatsAppConversation::factory()->greeting()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.greeting_identified'));

        $this->chatBot->handle($conv, textMsg('oi'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: AWAITING_NAME
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — AWAITING_NAME', function () {

    it('salva nome no payload e transita para AWAITING_AREA', function () {
        $conv = WhatsAppConversation::factory()->awaitingName()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('João Silva'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_AREA)
            ->and($conv->getPayloadValue('name'))->toBe('João Silva');
    });

    it('solicita nome novamente quando body é vazio', function () {
        $conv = WhatsAppConversation::factory()->awaitingName()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, 'Por favor, informe seu nome.');

        $this->chatBot->handle($conv, textMsg(''));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NAME);
    });

    it('solicita nome novamente quando body é somente espaços', function () {
        $conv = WhatsAppConversation::factory()->awaitingName()->create();

        $this->chatBot->handle($conv, textMsg('   '));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NAME);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: AWAITING_COMPANY
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — AWAITING_COMPANY', function () {

    it('vincula empresa encontrada e transita para AWAITING_AREA', function () {
        $company = Company::factory()->create(['name' => 'Amura Tech']);

        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $this->chatBotRepo->shouldReceive('findCompanyByName')
            ->with('Amura')
            ->andReturn($company);

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('Amura'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_AREA)
            ->and($conv->company_id)->toBe($company->id)
            ->and($conv->getPayloadValue('company_name'))->toBe('Amura');
    });

    it('avança para coleta de CNPJ quando nome não é encontrado', function () {
        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $this->chatBotRepo->shouldReceive('findCompanyByName')
            ->with('EmpresaInexistente XYZ')
            ->andReturn(null);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(
                fn (string $message) => str_contains($message, 'EmpresaInexistente XYZ')
                    && str_contains($message, 'CNPJ')
            ));

        $this->chatBot->handle($conv, textMsg('EmpresaInexistente XYZ'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ)
            ->and($conv->company_id)->toBeNull()
            ->and($conv->getPayloadValue('company_name'))->toBe('EmpresaInexistente XYZ')
            ->and($conv->getPayloadValue('company_name_attempted'))->toBe('EmpresaInexistente XYZ')
            ->and($conv->getPayloadValue('company_unidentified'))->toBeTrue();
    });

    it('valida CNPJ e avança para coleta de nome quando empresa encontrada', function () {
        $company = Company::factory()->create();
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_COMPANY_CNPJ,
        ]);

        $this->chatBotRepo->shouldReceive('findCompanyByCnpj')
            ->with('11222333000181')
            ->andReturn($company);

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('11.222.333/0001-81'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_NAME)
            ->and($conv->getPayloadValue('company_cnpj'))->toBe('11222333000181')
            ->and($conv->company_id)->toBe($company->id);
    });

    it('mantém coleta de CNPJ quando CNPJ é inválido', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_COMPANY_CNPJ,
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn (string $message) => str_contains($message, 'CNPJ inválido')));

        $this->chatBot->handle($conv, textMsg('11.111.111/1111-11'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

    it('normaliza telefone e avança para seleção de área', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_COMPANY_PHONE,
        ]);

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('(27) 99999-0001'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_AREA)
            ->and($conv->getPayloadValue('company_phone'))->toBe('27999990001');
    });

    it('solicita empresa quando body vazio', function () {
        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, 'Por favor, informe o nome da empresa.');

        $this->chatBot->handle($conv, textMsg(''));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY);
    });

    it('busca empresa pelo trade_name também', function () {
        $company = Company::factory()->create([
            'name'       => 'Razão Social LTDA',
            'trade_name' => 'Fantasia Comercial',
        ]);

        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        $this->chatBotRepo->shouldReceive('findCompanyByName')
            ->with('Fantasia')
            ->andReturn($company);

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('Fantasia'));

        expect($conv->fresh()->company_id)->toBe($company->id);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: AWAITING_AREA
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — AWAITING_AREA', function () {

    it('salva área Suporte e transita para AWAITING_PROBLEM', function (string $key, string $label) {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg($key));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_PROBLEM)
            ->and($conv->getPayloadValue('area_key'))->toBe($key)
            ->and($conv->getPayloadValue('area_label'))->toBe($label);
    })->with([
        ['1', 'Suporte Técnico'],
    ]);

    it('salva área Financeiro e transita para AWAITING_FINANCEIRO_CHOICE', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('2'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_FINANCEIRO_CHOICE)
            ->and($conv->getPayloadValue('area_key'))->toBe('2')
            ->and($conv->getPayloadValue('area_label'))->toBe('Financeiro');
    });

    it('salva área Comercial e transita para AWAITING_COMERCIAL_CHOICE', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('3'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMERCIAL_CHOICE)
            ->and($conv->getPayloadValue('area_key'))->toBe('3')
            ->and($conv->getPayloadValue('area_label'))->toBe('Comercial');
    });

    it('exibe opção inválida quando número não reconhecido', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.invalid_option'));

        $this->chatBot->handle($conv, textMsg('9'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_AREA);
    });

    it('exibe opção inválida para texto não numérico', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        $this->chatBot->handle($conv, textMsg('outro'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_AREA);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: AWAITING_PROBLEM
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — AWAITING_PROBLEM', function () {

    it('salva problema e transita para AWAITING_ATTACHMENTS', function () {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('Sistema não abre após atualização'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_ATTACHMENTS)
            ->and($conv->getPayloadValue('problem'))->toBe('Sistema não abre após atualização');
    });

    it('solicita descrição quando body vazio', function () {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, 'Por favor, descreva o problema.');

        $this->chatBot->handle($conv, textMsg(''));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_PROBLEM);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: AWAITING_ATTACHMENTS
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — AWAITING_ATTACHMENTS', function () {

    it('avança para CONFIRMING ao receber "confirmar"', function (string $keyword) {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg($keyword));

        expect($conv->fresh()->state)->toBe(ConversationState::CONFIRMING);
    })->with(['confirmar', 'confirm', 'ok', 'não', 'nao', 'n']);

    it('armazena mídia recebida e solicita mais', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        $this->whatsApp->shouldReceive('findInboundAttachmentPath')->andReturn(null);
        $this->whatsApp->shouldReceive('downloadAndStoreMedia')
            ->once()
            ->andReturn('whatsapp/attachments/uuid.jpg');

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'Anexo recebido')));

        $this->chatBot->handle($conv, mediaMsg('image'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_ATTACHMENTS)
            ->and($conv->getPayloadValue('attachments'))->toHaveCount(1);
    });

    it('avisa quando a mídia não pôde ser baixada', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        $this->whatsApp->shouldReceive('findInboundAttachmentPath')->andReturn(null);
        $this->whatsApp->shouldReceive('downloadAndStoreMedia')->andReturn(null);
        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'Não consegui receber esse anexo')));

        $this->chatBot->handle($conv, mediaMsg('image'));

        expect($conv->fresh()->getPayloadValue('attachments', []))->toBeEmpty();
    });

    it('mantém anexo no resumo após receber mídia e confirmar', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create([
            'payload' => [
                'name' => 'Maria Souza',
                'company_name' => 'Empresa Teste',
                'area_label' => 'Suporte Técnico',
                'problem' => 'Sistema não abre',
            ],
        ]);

        $this->whatsApp->shouldReceive('findInboundAttachmentPath')->andReturn(null);
        $this->whatsApp->shouldReceive('downloadAndStoreMedia')
            ->once()
            ->andReturn('whatsapp/attachments/uuid.jpg');

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'Anexo recebido')));

        $this->chatBot->handle($conv, mediaMsg('image'));

        $fresh = $conv->fresh();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($fresh, Mockery::on(fn ($msg) => str_contains($msg, 'Anexos: 1 arquivo(s)')));

        $this->chatBot->handle($fresh, textMsg('confirmar'));
    });

    it('solicita novamente quando texto não é reconhecido', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'confirmar')));

        $this->chatBot->handle($conv, textMsg('quero enviar mais'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_ATTACHMENTS);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: CONFIRMING
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — CONFIRMING', function () {

    it('cria ticket ao receber "confirmar"', function (string $keyword) {
        $conv   = WhatsAppConversation::factory()->confirming()->create();
        $ticket = Mockery::mock(Ticket::class)->makePartial();
        $ticket->id = 99;

        $this->tickets->shouldReceive('createFromConversation')
            ->once()
            ->with($conv)
            ->andReturn($ticket);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, '99')));

        $this->chatBot->handle($conv, textMsg($keyword));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conv->fresh()->ticket_id)->toBe(99);
    })->with(['confirmar', 'confirm', 'sim', 's', 'yes', 'ok']);

    it('cancela ao receber "cancelar"', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.cancelled'));

        $this->chatBot->handle($conv, textMsg('cancelar'));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    });

    it('reexibe resumo para resposta desconhecida', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'Resumo')));

        $this->chatBot->handle($conv, textMsg('talvez'));

        expect($conv->fresh()->state)->toBe(ConversationState::CONFIRMING);
    });

    it('envia mensagem de erro quando createFromConversation lança exceção', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        $this->tickets->shouldReceive('createFromConversation')
            ->andThrow(new \RuntimeException('Falha DB'));

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.error'));

        $this->chatBot->handle($conv, textMsg('confirmar'));
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estados terminais → reiniciar
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — estados terminais reiniciam conversa', function () {

    it('reinicia quando estado é COMPLETED', function () {
        $conv = WhatsAppConversation::factory()->completed()->create([
            'payload' => ['name' => 'Antigo'],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.greeting_identified'));

        $this->chatBot->handle($conv, textMsg('nova mensagem'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ)
            ->and($conv->payload)->toBe([]);
    });

    it('reinicia quando estado é CANCELLED', function () {
        $conv = WhatsAppConversation::factory()->cancelled()->create();

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('recomeçar'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: AWAITING_MENU
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — AWAITING_MENU', function () {

    it('opção 1 inicia o fluxo de abertura de chamado (transita para AWAITING_COMPANY_CNPJ)', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.greeting_identified'));

        $this->chatBot->handle($conv, textMsg('1'));

        expect($conv->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::AWAITING_COMPANY_CNPJ);
    });

    it('opção 3 realiza handover e transita para HUMAN_PENDING', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.human_pending'));

        $this->chatBot->handle($conv, textMsg('3'));

        expect($conv->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::HUMAN_PENDING);
    });

    it('opção inválida reapresenta o menu sem mudar estado', function (string $input) {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(
                fn ($msg) => str_contains($msg, config('whatsapp.messages.invalid_option'))
                    && str_contains($msg, config('whatsapp.messages.menu'))
            ));

        $this->chatBot->handle($conv, textMsg($input));

        expect($conv->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::AWAITING_MENU);
    })->with(['9', 'oi']);

    it('opção 2 consulta status e volta ao menu (sem chamado encontrado)', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $this->chatBotRepo->shouldReceive('findLastTicketByCompanyId')->andReturn(null)->byDefault();
        $this->chatBotRepo->shouldReceive('findLastTicketByPhone')->andReturn(null);

        $this->whatsApp->shouldReceive('send')->twice();

        $this->chatBot->handle($conv, textMsg('2'));

        expect($conv->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::AWAITING_MENU);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Estado: HUMAN_PENDING
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — HUMAN_PENDING', function () {

    it('mensagens em human_pending não disparam respostas do bot', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create();

        $this->whatsApp->shouldReceive('send')->never();

        $this->chatBot->handle($conv, textMsg('ainda estou esperando'));

        // Estado permanece HUMAN_PENDING
        expect($conv->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::HUMAN_PENDING);
    });

    it('cancelamento universal não afeta estado human_pending', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create();

        $this->whatsApp->shouldReceive('send')->never();

        // "cancelar" é ignorado quando em human_pending (bot silencioso)
        $this->chatBot->handle($conv, textMsg('cancelar'));

        expect($conv->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::HUMAN_PENDING);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Fluxo: Cliente NÃO localizado (CNPJ não encontrado → Comercial)
// ─────────────────────────────────────────────────────────────────────────────

describe('ChatBotService — fluxo cliente não localizado', function () {

    it('CNPJ não encontrado transita para AWAITING_NOT_FOUND_CHOICE e envia mensagem com opções', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_COMPANY_CNPJ,
        ]);

        $this->chatBotRepo->shouldReceive('findCompanyByCnpj')
            ->with('11222333000181')
            ->andReturn(null);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.cnpj_not_found'));

        $this->chatBot->handle($conv, textMsg('11.222.333/0001-81'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NOT_FOUND_CHOICE);
    });

    it('opção 1 (Comercial) transita para AWAITING_NOT_FOUND_NAME', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_CHOICE,
            'payload' => ['company_cnpj' => '11222333000181'],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.not_found_comercial'));

        $this->chatBot->handle($conv, textMsg('1'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NOT_FOUND_NAME);
    });

    it('opção 2 (tentar novamente) volta para AWAITING_COMPANY_CNPJ', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_CHOICE,
        ]);

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('2'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

    it('opção 3 (encerrar) finaliza com goodbye e COMPLETED', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_CHOICE,
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.goodbye'));

        $this->chatBot->handle($conv, textMsg('3'));

        expect($conv->fresh()->state)->toBe(ConversationState::COMPLETED);
    });

    it('coleta nome no fluxo não localizado e transita para AWAITING_NOT_FOUND_COMPANY', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_NAME,
            'payload' => ['company_cnpj' => '11222333000181'],
        ]);

        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('João Silva'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_NOT_FOUND_COMPANY)
            ->and($conv->getPayloadValue('name'))->toBe('João Silva');
    });

    it('coleta nome da empresa e transita para AWAITING_NOT_FOUND_PHONE com phone_choice', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_COMPANY,
            'payload' => ['name' => 'João Silva', 'company_cnpj' => '11222333000181'],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.phone_choice'));

        $this->chatBot->handle($conv, textMsg('Empresa Nova LTDA'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_NOT_FOUND_PHONE)
            ->and($conv->getPayloadValue('company_name'))->toBe('Empresa Nova LTDA');
    });

    it('opção 1 (usar número atual) envia phone_registered + ack + resumo e transita direto para CONFIRMING', function () {
        $conv = WhatsAppConversation::factory()->create([
            'phone' => '5527999990000',
            'state' => ConversationState::AWAITING_NOT_FOUND_PHONE,
            'payload' => [
                'name' => 'João Silva',
                'company_name' => 'Empresa Nova LTDA',
                'company_cnpj' => '11222333000181',
            ],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.phone_registered'));
        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.not_found_acknowledged'));
        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'João Silva')
                && str_contains($msg, 'Empresa Nova LTDA')
                && str_contains($msg, 'Comercial')
                && ! str_contains($msg, 'Descrição')
                && ! str_contains($msg, 'Problema')
            ));

        $this->chatBot->handle($conv, textMsg('1'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::CONFIRMING)
            ->and($conv->getPayloadValue('company_phone'))->toBe('5527999990000')
            ->and($conv->getPayloadValue('area_label'))->toBe('Comercial')
            ->and($conv->getPayloadValue('problem'))->toBe(config('whatsapp.messages.not_found_default_problem'));
    });

    it('opção 2 (informar outro) pergunta novo telefone e mantém AWAITING_NOT_FOUND_PHONE', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_PHONE,
            'payload' => [
                'name' => 'João Silva',
                'company_name' => 'Empresa Nova',
            ],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.ask_phone_other'));

        $this->chatBot->handle($conv, textMsg('2'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NOT_FOUND_PHONE);
    });

    it('informar outro telefone direto vai para CONFIRMING (sem pedir descrição)', function () {
        $conv = WhatsAppConversation::factory()->create([
            'phone' => '5527999990000',
            'state' => ConversationState::AWAITING_NOT_FOUND_PHONE,
            'payload' => [
                'name' => 'Maria',
                'company_name' => 'Outra Empresa',
                'company_cnpj' => '11222333000181',
            ],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, config('whatsapp.messages.not_found_acknowledged'));
        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'Maria')
                && str_contains($msg, 'Outra Empresa')
                && str_contains($msg, 'Comercial')
            ));

        $this->chatBot->handle($conv, textMsg('(27) 99888-7777'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::CONFIRMING)
            ->and($conv->getPayloadValue('company_phone'))->toBe('27998887777')
            ->and($conv->getPayloadValue('area_label'))->toBe('Comercial')
            ->and($conv->getPayloadValue('problem'))->not->toBeEmpty();
    });

    it('telefone inválido (menos de 10 dígitos) mantém AWAITING_NOT_FOUND_PHONE', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_NOT_FOUND_PHONE,
            'payload' => ['name' => 'João', 'company_name' => 'Empresa'],
        ]);

        $this->whatsApp->shouldReceive('send')->once()
            ->with($conv, Mockery::on(fn ($msg) => str_contains($msg, 'Telefone inválido')));

        $this->chatBot->handle($conv, textMsg('99999'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NOT_FOUND_PHONE);
    });

    it('confirmação após resumo cria ticket usando descrição padrão do fluxo não localizado', function () {
        $conv = WhatsAppConversation::factory()->create([
            'phone' => '5527999990000',
            'state' => ConversationState::CONFIRMING,
            'payload' => [
                'name' => 'João Silva',
                'company_name' => 'Empresa Nova LTDA',
                'company_cnpj' => '11222333000181',
                'company_phone' => '5527999990000',
                'area_key' => '3',
                'area_label' => 'Comercial',
                'problem' => config('whatsapp.messages.not_found_default_problem'),
            ],
        ]);

        $ticket = Mockery::mock(Ticket::class)->makePartial();
        $ticket->id = 107;

        $this->tickets->shouldReceive('createFromConversation')->once()->with($conv)->andReturn($ticket);
        $this->whatsApp->shouldReceive('send')->once();

        $this->chatBot->handle($conv, textMsg('confirmar'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conv->ticket_id)->toBe(107);
    });

});
