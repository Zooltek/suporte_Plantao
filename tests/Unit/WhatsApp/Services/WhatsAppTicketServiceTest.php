<?php

use App\Contracts\Repositories\WhatsAppTicketRepositoryInterface;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\CompanyPhoneLookupService;
use App\Services\WhatsApp\WhatsAppTicketService;
use Illuminate\Support\Facades\Storage;

// ─────────────────────────────────────────────────────────────────────────────
// WhatsAppTicketService
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Storage::fake('public');
    Company::factory()->create(['name' => 'Empresa Teste', 'trade_name' => null]);
    config([
        'whatsapp.chatbot.assign_default_agent' => false,
        'whatsapp.chatbot.routing_departments' => ['1' => null, '2' => null, '3' => null],
    ]);

    $this->service = new WhatsAppTicketService(
        app(WhatsAppTicketRepositoryInterface::class),
        app(CompanyPhoneLookupService::class)
    );
});

// Helpers ------------------------------------------------------------------

function fullPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Maria Souza',
        'company_name' => 'Empresa Teste',
        'area_key' => '1',
        'area_label' => 'Suporte Técnico',
        'problem' => 'Sistema não inicia após atualização',
        'attachments' => [],
    ], $overrides);
}

function createCategoryRow(string $name, int $parentId): int
{
    $existing = \DB::table('solutions_category')
        ->join(
            'solutions_category_description',
            'solutions_category.category_id',
            '=',
            'solutions_category_description.category_id'
        )
        ->whereRaw('LOWER(solutions_category_description.name) = ?', [mb_strtolower($name)])
        ->where('solutions_category.parent_id', $parentId)
        ->value('solutions_category.category_id');

    if ($existing) {
        return (int) $existing;
    }

    $categoryId = \DB::table('solutions_category')->insertGetId([
        'parent_id' => $parentId,
        'priority' => 'low',
        'status' => 1,
        'visible' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    \DB::table('solutions_category_description')->insert([
        'category_id' => $categoryId,
        'name' => $name,
        'permalink' => \Illuminate\Support\Str::slug($name),
        'description' => "Categoria de teste: {$name}",
    ]);

    return (int) $categoryId;
}

// ─────────────────────────────────────────────────────────────────────────────
// createFromConversation — casos de sucesso
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppTicketService::createFromConversation() — sucesso', function () {

    it('cria ticket com dados básicos do payload', function () {
        $admin = User::factory()->admin()->create();

        $conv = WhatsAppConversation::factory()->create([
            'phone' => '5527999990001',
            'payload' => fullPayload(),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket)->toBeInstanceOf(Ticket::class)
            ->and($ticket->exists)->toBeTrue()
            ->and($ticket->contact)->toBe('MARIA SOUZA')
            ->and($ticket->trouble)->toBe('Sistema não inicia após atualização')
            ->and($ticket->origin_id)->toBe(config('whatsapp.chatbot.origin_id', 5));

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'origin_id' => config('whatsapp.chatbot.origin_id', 5),
        ]);
    });

    it('usa company_id da conversa quando disponível', function () {
        User::factory()->admin()->create();
        $company = Company::factory()->create();

        $conv = WhatsAppConversation::factory()->create([
            'company_id' => $company->id,
            'payload' => fullPayload(['company_name' => $company->name]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->company_id)->toBe($company->id);
    });

    it('resolve empresa por nome quando company_id está null', function () {
        User::factory()->admin()->create();
        $company = Company::factory()->create(['name' => 'Empresa Resolvida']);

        $conv = WhatsAppConversation::factory()->create([
            'company_id' => null,
            'payload' => fullPayload(['company_name' => 'Empresa Resolvida']),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->company_id)->toBe($company->id);
    });

    it('mantém chamado de suporte sem agente mesmo com agent padrão configurado', function () {
        $agent = User::factory()->admin()->create();
        config([
            'whatsapp.chatbot.default_agent_id' => $agent->id,
            'whatsapp.chatbot.assign_default_agent' => true,
        ]);

        $conv = WhatsAppConversation::factory()->create(['payload' => fullPayload()]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->agent_id)->toBeNull()
            ->and($ticket->status_id)->toBe(Ticket::STATUS_PENDING_ID);
    });

    it('usa primeiro admin ativo como autor sem atribuir agente', function () {
        config(['whatsapp.chatbot.default_agent_id' => null]);
        $admin = User::factory()->admin()->create(['active' => true]);

        $conv = WhatsAppConversation::factory()->create(['payload' => fullPayload()]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->author_id)->toBe($admin->id)
            ->and($ticket->user_id)->toBe($admin->id)
            ->and($ticket->agent_id)->toBeNull();
    });

    it('mapeia área_key para category_id correto', function (string $key) {
        User::factory()->admin()->create();
        config([
            'WHATSAPP_CATEGORY_SUPORTE' => 10,
            'WHATSAPP_CATEGORY_FINANCEIRO' => 20,
            'WHATSAPP_CATEGORY_COMERCIAL' => 30,
        ]);

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload(['area_key' => $key]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->exists)->toBeTrue();
    })->with(['1', '2', '3']);

    it('usa categoria "Atendimento" e subcategoria correspondente à área escolhida', function (
        string $areaKey,
        string $expectedSubName,
    ) {
        User::factory()->admin()->create();

        $atendimentoId = createCategoryRow('Atendimento', 0);
        $suporteId = createCategoryRow('Suporte', $atendimentoId);
        $financeiroId = createCategoryRow('Financeiro', $atendimentoId);
        $comercialId = createCategoryRow('Comercial', $atendimentoId);

        $expectedSubId = match ($expectedSubName) {
            'Suporte' => $suporteId,
            'Financeiro' => $financeiroId,
            'Comercial' => $comercialId,
        };

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload(['area_key' => $areaKey]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->category_id)->toBe($atendimentoId)
            ->and($ticket->sub_category_id)->toBe($expectedSubId);
    })->with([
        'suporte' => ['1', 'Suporte'],
        'financeiro' => ['2', 'Financeiro'],
        'comercial' => ['3', 'Comercial'],
    ]);

    it('roteia chamados para a fila pendente do departamento selecionado sem atribuir agente', function (
        string $areaKey,
        string $areaLabel,
    ) {
        $department = Department::factory()->create(['name' => $areaLabel]);
        $defaultAgent = User::factory()->agent()->create(['department_id' => $department->id]);
        User::factory()->admin()->create();

        config([
            "whatsapp.chatbot.routing_departments.{$areaKey}" => $department->id,
            'whatsapp.chatbot.assign_default_agent' => true,
            'whatsapp.chatbot.default_agent_id' => $defaultAgent->id,
            'whatsapp.chatbot.default_status_id' => 1,
        ]);

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload([
                'area_key' => $areaKey,
                'area_label' => $areaLabel,
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->agent_id)->toBeNull()
            ->and($ticket->status_id)->toBe(Ticket::STATUS_PENDING_ID)
            ->and($ticket->department_id)->toBe($department->id);
    })->with([
        'suporte' => ['1', 'Suporte Técnico'],
        'financeiro' => ['2', 'Financeiro'],
        'comercial' => ['3', 'CRM / Comercial'],
    ]);

    it('cria notificação push para agentes da fila do setor', function () {
        $support = Department::factory()->create(['name' => 'Suporte Técnico']);
        $finance = Department::factory()->create(['name' => 'Financeiro']);
        config(['whatsapp.chatbot.routing_departments.1' => $support->id]);

        User::factory()->admin()->create(['department_id' => $finance->id]);
        $supportAgent = User::factory()->agent()->create(['department_id' => $support->id]);
        $financeAgent = User::factory()->agent()->create(['department_id' => $finance->id]);

        $conv = WhatsAppConversation::factory()->create(['payload' => fullPayload()]);

        $this->service->createFromConversation($conv);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $supportAgent->id,
            'status' => 1,
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $financeAgent->id,
            'status' => 1,
        ]);
    });

    it('anexa arquivos de mídia ao ticket criado', function () {
        User::factory()->admin()->create();

        $filePath = 'whatsapp/attachments/test-file.pdf';
        Storage::disk('public')->put($filePath, 'fake pdf content');

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload([
                'attachments' => [
                    ['path' => $filePath, 'mime_type' => 'application/pdf'],
                ],
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        $this->assertDatabaseHas('ticketit_attachments', [
            'ticket_id' => $ticket->id,
            'mime' => 'application/pdf',
        ]);
    });

    it('preserva nome original do arquivo enviado pelo cliente no chatbot', function () {
        User::factory()->admin()->create();

        $filePath = 'whatsapp/attachments/'.\Illuminate\Support\Str::uuid().'.pdf';
        Storage::disk('public')->put($filePath, 'fake pdf content');

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload([
                'attachments' => [
                    [
                        'path' => $filePath,
                        'mime_type' => 'application/pdf',
                        'original_filename' => 'contrato-cliente.pdf',
                    ],
                ],
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        $this->assertDatabaseHas('ticketit_attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'contrato-cliente.pdf',
            'disk_path' => $filePath,
        ]);
    });

    it('cai para o basename do path quando original_filename está ausente', function () {
        User::factory()->admin()->create();

        $filePath = 'whatsapp/attachments/sem-nome-original.jpg';
        Storage::disk('public')->put($filePath, 'fake image');

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload([
                'attachments' => [
                    ['path' => $filePath, 'mime_type' => 'image/jpeg'],
                ],
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        $this->assertDatabaseHas('ticketit_attachments', [
            'ticket_id' => $ticket->id,
            'original_name' => 'sem-nome-original.jpg',
        ]);
    });

    it('ignora arquivo de mídia quando path não existe no storage', function () {
        User::factory()->admin()->create();

        $conv = WhatsAppConversation::factory()->create([
            'payload' => fullPayload([
                'attachments' => [
                    ['path' => 'whatsapp/attachments/nao-existe.jpg', 'mime_type' => 'image/jpeg'],
                ],
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        $this->assertDatabaseMissing('ticketit_attachments', ['ticket_id' => $ticket->id]);
    });

    it('cria cadastro mínimo e ticket quando empresa não é encontrada nem vinculada', function () {
        User::factory()->admin()->create();

        $conv = WhatsAppConversation::factory()->create([
            'phone' => '5527999990001',
            'company_id' => null,
            'payload' => fullPayload([
                'company_name' => 'EmpresaQueNaoExiste999',
                'company_unidentified' => true,
                'company_cnpj' => '11222333000181',
                'company_phone' => '27999990001',
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->company_id)->not->toBeNull()
            ->and($ticket->agent_id)->toBeNull()
            ->and($ticket->origin_id)->toBe(config('whatsapp.chatbot.origin_id', 5));

        $this->assertDatabaseHas('customers', [
            'id' => $ticket->company_id,
            'name' => 'EmpresaQueNaoExiste999',
            'cnpj' => '11.222.333/0001-81',
            'phone' => '27999990001',
        ]);
        $this->assertDatabaseHas('ticketit', [
            'contact' => 'MARIA SOUZA',
            'trouble' => 'Sistema não inicia após atualização',
        ]);
    });

    it('reaproveita empresa existente quando CNPJ apenas difere de formatação', function () {
        User::factory()->admin()->create();

        $existing = Company::factory()->create([
            'name' => 'Empresa Já Cadastrada',
            'cnpj' => '36.423.135/0001-07',
        ]);

        $conv = WhatsAppConversation::factory()->create([
            'phone' => '5527999990777',
            'company_id' => null,
            'payload' => fullPayload([
                'company_name' => 'Empresa Já Cadastrada',
                'company_unidentified' => true,
                'company_cnpj' => '36423135000107',
                'company_phone' => '27999990777',
            ]),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->company_id)->toBe($existing->id)
            ->and(Company::whereCnpjDigits('36423135000107')->count())->toBe(1);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// createFromConversation — casos de erro
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppTicketService::createFromConversation() — erro', function () {

    it('lança WhatsAppPayloadException quando name está ausente', function () {
        $conv = WhatsAppConversation::factory()->create([
            'payload' => ['problem' => 'Descrição do problema'],
        ]);

        expect(fn () => $this->service->createFromConversation($conv))
            ->toThrow(\App\Exceptions\WhatsAppPayloadException::class, 'Payload incompleto');
    });

    it('lança WhatsAppPayloadException quando problem está ausente', function () {
        $conv = WhatsAppConversation::factory()->create([
            'payload' => ['name' => 'João'],
        ]);

        expect(fn () => $this->service->createFromConversation($conv))
            ->toThrow(\App\Exceptions\WhatsAppPayloadException::class, 'Payload incompleto');
    });

    it('lança WhatsAppPayloadException quando payload é null', function () {
        $conv = WhatsAppConversation::factory()->create(['payload' => null]);

        expect(fn () => $this->service->createFromConversation($conv))
            ->toThrow(\App\Exceptions\WhatsAppPayloadException::class);
    });

});
