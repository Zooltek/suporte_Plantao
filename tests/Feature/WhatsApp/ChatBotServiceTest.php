<?php

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

/**
 * Testa a máquina de estados do ChatBotService.
 *
 * WhatsAppService e WhatsAppTicketService são mockados para isolar
 * exclusivamente a lógica de transição de estado e persistência de payload.
 */
afterEach(fn () => Mockery::close());

function wa(string $body, string $type = 'text'): IncomingMessage
{
    return new IncomingMessage(
        from: '5527999999999',
        body: $body,
        type: $type,
        mediaUrl: null,
        mediaId: null,
        mimetype: null,
        timestamp: (string) time(),
        messageId: uniqid('wa_'),
    );
}

function makeChatbot(
    ?WhatsAppService $whatsApp = null,
    ?WhatsAppTicketService $ticketService = null,
    ?ChatBotRepositoryInterface $chatBotRepository = null,
    ?CompanyPhoneLookupService $companyPhoneLookup = null,
): ChatBotService {
    $whatsApp ??= Mockery::mock(WhatsAppService::class)->shouldIgnoreMissing();
    $ticketService ??= Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing();
    $chatBotRepository ??= app(ChatBotRepositoryInterface::class);
    $companyPhoneLookup ??= app(CompanyPhoneLookupService::class);

    return new ChatBotService($whatsApp, $ticketService, $chatBotRepository, $companyPhoneLookup);
}

describe('ChatBotService — GREETING', function () {

    it('qualquer mensagem envia saudação e avança para AWAITING_COMPANY_CNPJ', function () {
        $conv = WhatsAppConversation::factory()->greeting()->create();

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')->once();
        $bot = makeChatbot($whatsApp, Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing());

        $bot->handle($conv, wa('oi'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

    it('número já cadastrado pula CNPJ e vai direto para AWAITING_NAME', function () {
        $company = Company::factory()->create([
            'name' => 'Empresa Vinculada Ltda',
            'trade_name' => 'Vinculada',
            'phone' => '5527999999999',
        ]);

        $conv = WhatsAppConversation::factory()->greeting()->create([
            'phone' => '5527999999999',
        ]);

        makeChatbot()->handle($conv, wa('oi'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_NAME);
        expect($conv->company_id)->toBe($company->id);
        expect($conv->getPayloadValue('company_name'))->toBe('Vinculada');
    });

    it('empresa identificada por telefone salva o CNPJ normalizado no payload', function () {
        $company = Company::factory()->create([
            'name' => 'Empresa Vinculada Ltda',
            'trade_name' => 'Vinculada',
            'phone' => '5527999999999',
            'cnpj' => '11.222.333/0001-81',
        ]);

        $conv = WhatsAppConversation::factory()->greeting()->create([
            'phone' => '5527999999999',
        ]);

        makeChatbot()->handle($conv, wa('oi'));

        $conv->refresh();
        expect($conv->company_id)->toBe($company->id);
        expect($conv->getPayloadValue('company_cnpj'))->toBe('11222333000181');
    });

});

describe('ChatBotService — AWAITING_MENU', function () {

    it('opção "1" inicia fluxo de ticket (→ AWAITING_COMPANY_CNPJ)', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        makeChatbot()->handle($conv, wa('1'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

    it('opção "3" inicia handover humano (→ HUMAN_PENDING)', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        makeChatbot()->handle($conv, wa('3'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('opção inválida não muda estado', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        makeChatbot()->handle($conv, wa('9'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_MENU);
    });

});

describe('ChatBotService — AWAITING_NAME', function () {

    it('nome salvo e avança para AWAITING_AREA', function () {
        $conv = WhatsAppConversation::factory()->awaitingName()->create();

        makeChatbot()->handle($conv, wa('João Silva'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_AREA);
        expect($conv->getPayloadValue('name'))->toBe('João Silva');
    });

    it('nome vazio não avança', function () {
        $conv = WhatsAppConversation::factory()->awaitingName()->create();

        makeChatbot()->handle($conv, wa(''));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_NAME);
    });

});

describe('ChatBotService — AWAITING_COMPANY', function () {

    it('empresa encontrada é vinculada e avança para AWAITING_AREA', function () {
        $company = Company::factory()->create(['name' => 'Acme Corp', 'trade_name' => 'Acme']);
        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        makeChatbot()->handle($conv, wa('Acme'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_AREA);
        expect($conv->company_id)->toBe($company->id);
    });

    it('empresa não encontrada avança para coleta de dados básicos', function () {
        $conv = WhatsAppConversation::factory()->awaitingCompany()->create();

        makeChatbot()->handle($conv, wa('EmpresaInexistente XYZ 9999'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
        expect($conv->company_id)->toBeNull();
        expect($conv->getPayloadValue('company_name'))->toBe('EmpresaInexistente XYZ 9999');
        expect($conv->getPayloadValue('company_name_attempted'))->toBe('EmpresaInexistente XYZ 9999');
        expect($conv->getPayloadValue('company_unidentified'))->toBeTrue();
    });

});

describe('ChatBotService — AWAITING_AREA', function () {

    it('opção "1" (Suporte Técnico) salva área e avança para AWAITING_PROBLEM', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        makeChatbot()->handle($conv, wa('1'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_PROBLEM);
        expect($conv->getPayloadValue('area_key'))->toBe('1');
        expect($conv->getPayloadValue('area_label'))->toBe('Suporte Técnico');
    });

    it('opção "2" (Financeiro) salva área correta', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        makeChatbot()->handle($conv, wa('2'));

        $conv->refresh();
        expect($conv->getPayloadValue('area_label'))->toBe('Financeiro');
    });

    it('opção inválida não avança', function () {
        $conv = WhatsAppConversation::factory()->awaitingArea()->create();

        makeChatbot()->handle($conv, wa('7'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_AREA);
    });

});

describe('ChatBotService — AWAITING_PROBLEM', function () {

    it('descrição salva e avança para AWAITING_ATTACHMENTS', function () {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        makeChatbot()->handle($conv, wa('Sistema trava ao abrir relatórios'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_ATTACHMENTS);
        expect($conv->getPayloadValue('problem'))->toBe('Sistema trava ao abrir relatórios');
    });

    it('descrição vazia não avança', function () {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        makeChatbot()->handle($conv, wa(''));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_PROBLEM);
    });

});

describe('ChatBotService — AWAITING_ATTACHMENTS', function () {

    it('"confirmar" avança para CONFIRMING', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        makeChatbot()->handle($conv, wa('confirmar'));

        expect($conv->fresh()->state)->toBe(ConversationState::CONFIRMING);
    });

    it('"ok" também avança para CONFIRMING', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        makeChatbot()->handle($conv, wa('ok'));

        expect($conv->fresh()->state)->toBe(ConversationState::CONFIRMING);
    });

    it('"nao" avança para CONFIRMING sem anexo', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        makeChatbot()->handle($conv, wa('nao'));

        expect($conv->fresh()->state)->toBe(ConversationState::CONFIRMING);
    });

    it('mensagem de texto genérica solicita novamente sem avançar', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        makeChatbot()->handle($conv, wa('algum texto aleatorio'));

        expect($conv->fresh()->state)->toBe(ConversationState::AWAITING_ATTACHMENTS);
    });

    it('mídia reaproveita attachment_path da inbound message sem chamar downloadAndStoreMedia', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        $messageId = uniqid('wa_media_');
        $storedPath = 'whatsapp/attachments/reused-by-handler.jpg';

        \App\Models\WhatsApp\WhatsAppMessage::create([
            'conversation_id' => $conv->id,
            'direction' => 'inbound',
            'type' => 'image',
            'body' => null,
            'attachment_path' => $storedPath,
            'mime_type' => 'image/jpeg',
            'provider_message_id' => $messageId,
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('findInboundAttachmentPath')
            ->with($messageId)
            ->once()
            ->andReturn($storedPath);
        $whatsApp->shouldNotReceive('downloadAndStoreMedia');
        $whatsApp->shouldReceive('send')->once();

        $bot = makeChatbot($whatsApp, Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing());

        $media = new IncomingMessage(
            from: '5527999999999',
            body: '',
            type: 'image',
            mediaUrl: null,
            mediaId: 'evo-media-id',
            mimetype: 'image/jpeg',
            timestamp: (string) time(),
            messageId: $messageId,
        );

        $bot->handle($conv, $media);

        $attachments = $conv->fresh()->getPayloadValue('attachments', []);
        expect($attachments)->toHaveCount(1);
        expect($attachments[0]['path'])->toBe($storedPath);
        expect($attachments[0]['mime_type'])->toBe('image/jpeg');
    });

    it('preserva nome original do arquivo enviado pelo cliente no payload', function () {
        $conv = WhatsAppConversation::factory()->awaitingAttachments()->create();

        $messageId = uniqid('wa_media_');
        $storedPath = 'whatsapp/attachments/uuid-interno.pdf';

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('findInboundAttachmentPath')
            ->with($messageId)
            ->once()
            ->andReturn($storedPath);
        $whatsApp->shouldReceive('send')->once();

        $bot = makeChatbot($whatsApp, Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing());

        $media = new IncomingMessage(
            from: '5527999999999',
            body: '',
            type: 'document',
            mediaUrl: null,
            mediaId: 'evo-media-id',
            mimetype: 'application/pdf',
            timestamp: (string) time(),
            messageId: $messageId,
            fileName: 'contrato-cliente.pdf',
        );

        $bot->handle($conv, $media);

        $attachments = $conv->fresh()->getPayloadValue('attachments', []);
        expect($attachments)->toHaveCount(1);
        expect($attachments[0]['original_filename'])->toBe('contrato-cliente.pdf');
    });

});

describe('ChatBotService — CONFIRMING', function () {

    it('"sim" cria ticket e mantém conversa ativa para atendimento humano', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();
        $ticket = Ticket::factory()->create();

        $whatsApp = Mockery::mock(WhatsAppService::class)->shouldIgnoreMissing();
        $ticketSvc = Mockery::mock(WhatsAppTicketService::class);
        $ticketSvc->shouldReceive('createFromConversation')->once()->andReturn($ticket);

        $bot = makeChatbot($whatsApp, $ticketSvc);
        $bot->handle($conv, wa('sim'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::HUMAN_PENDING);
        expect($conv->ticket_id)->toBe($ticket->id);
        expect($conv->completed_at)->toBeNull();
    });

    it('"confirmar" também cria ticket', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();
        $ticket = Ticket::factory()->create();

        $ticketSvc = Mockery::mock(WhatsAppTicketService::class);
        $ticketSvc->shouldReceive('createFromConversation')->once()->andReturn($ticket);

        $bot = makeChatbot(Mockery::mock(WhatsAppService::class)->shouldIgnoreMissing(), $ticketSvc);
        $bot->handle($conv, wa('confirmar'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('"cancelar" vai para CANCELLED', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        makeChatbot()->handle($conv, wa('cancelar'));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    });

    it('opção "1" confirma e cria ticket', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();
        $ticket = Ticket::factory()->create();

        $ticketSvc = Mockery::mock(WhatsAppTicketService::class);
        $ticketSvc->shouldReceive('createFromConversation')->once()->andReturn($ticket);

        $bot = makeChatbot(Mockery::mock(WhatsAppService::class)->shouldIgnoreMissing(), $ticketSvc);
        $bot->handle($conv, wa('1'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('opção "2" cancela e vai para CANCELLED', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        makeChatbot()->handle($conv, wa('2'));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    });

    it('"não" vai para CANCELLED', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        makeChatbot()->handle($conv, wa('não'));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    });

    it('resposta inesperada reexibe resumo sem mudar estado', function () {
        $conv = WhatsAppConversation::factory()->confirming()->create();

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')->once(); // reexibe resumo

        $bot = makeChatbot($whatsApp, Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing());
        $bot->handle($conv, wa('hmmm não sei'));

        expect($conv->fresh()->state)->toBe(ConversationState::CONFIRMING);
    });

});

describe('ChatBotService — AWAITING_COMPANY_CNPJ', function () {

    it('aceita CNPJ com pontuação e localiza a empresa', function () {
        $company = Company::factory()->create(['cnpj' => '11222333000181']);
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_COMPANY_CNPJ,
        ]);

        makeChatbot()->handle($conv, wa('11.222.333/0001-81'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_NAME);
        expect($conv->company_id)->toBe($company->id);
        expect($conv->getPayloadValue('company_cnpj'))->toBe('11222333000181');
    });

    it('aceita CNPJ apenas com números e localiza a empresa', function () {
        $company = Company::factory()->create(['cnpj' => '11222333000181']);
        $conv = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_COMPANY_CNPJ,
        ]);

        makeChatbot()->handle($conv, wa('11222333000181'));

        expect($conv->fresh()->company_id)->toBe($company->id);
    });

});

describe('ChatBotService — comportamentos globais', function () {

    it('"cancelar" em qualquer estado intermediário cancela a conversa', function () {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        makeChatbot()->handle($conv, wa('cancelar'));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    });

    it('"sair" também cancela', function () {
        $conv = WhatsAppConversation::factory()->awaitingProblem()->create();

        makeChatbot()->handle($conv, wa('sair'));

        expect($conv->fresh()->state)->toBe(ConversationState::CANCELLED);
    });

    it('sessão expirada reinicia payload e volta para AWAITING_COMPANY_CNPJ', function () {
        $conv = WhatsAppConversation::factory()->expired()->create([
            'payload' => ['name' => 'Usuário Antigo', 'company_name' => 'Empresa Velha'],
        ]);

        makeChatbot()->handle($conv, wa('oi'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
        expect($conv->payload)->toBeEmpty();
        expect($conv->ticket_id)->toBeNull();
    });

    it('HUMAN_PENDING: bot silencia e apenas atualiza last_activity_at', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'last_activity_at' => now()->subMinutes(10),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        $bot = makeChatbot($whatsApp, Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing());
        $bot->handle($conv, wa('quero falar com alguém'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('HUMAN_PENDING: envio de "2" não cancela e nem finaliza o atendimento', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'last_activity_at' => now()->subMinutes(2),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        $bot = makeChatbot($whatsApp, Mockery::mock(WhatsAppTicketService::class)->shouldIgnoreMissing());
        $bot->handle($conv, wa('2'));

        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('COMPLETED: nova mensagem reinicia a conversa', function () {
        $conv = WhatsAppConversation::factory()->completed()->create();

        makeChatbot()->handle($conv, wa('quero abrir outro chamado'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
        expect($conv->payload)->toBeEmpty();
    });

    it('CANCELLED: nova mensagem reinicia a conversa', function () {
        $conv = WhatsAppConversation::factory()->cancelled()->create();

        makeChatbot()->handle($conv, wa('quero tentar novamente'));

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);
    });

});
