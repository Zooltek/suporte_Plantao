<?php

use App\Contracts\Repositories\WhatsAppTicketRepositoryInterface;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\CompanyPhoneLookupService;
use App\Services\WhatsApp\WhatsAppTicketService;
use Illuminate\Support\Facades\Storage;

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

function wadt_payload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Maria Souza',
        'company_name' => 'Empresa Teste',
        'area_key' => '3',
        'area_label' => 'CRM / Comercial',
        'problem' => 'Quero negociar contrato',
        'attachments' => [],
    ], $overrides);
}

function wadt_seed_category_tree(?int $parentDeptId, ?int $comercialSubDeptId): array
{
    $parentId = \DB::table('solutions_category')->insertGetId([
        'parent_id' => 0,
        'priority' => 'low',
        'status' => 1,
        'visible' => 1,
        'department_id' => $parentDeptId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('solutions_category_description')->insert([
        'category_id' => $parentId,
        'name' => 'Atendimento',
        'permalink' => 'atendimento-'.uniqid(),
        'description' => 'Atendimento',
    ]);

    $subId = \DB::table('solutions_category')->insertGetId([
        'parent_id' => $parentId,
        'priority' => 'low',
        'status' => 1,
        'visible' => 1,
        'department_id' => $comercialSubDeptId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('solutions_category_description')->insert([
        'category_id' => $subId,
        'name' => 'Comercial',
        'permalink' => 'comercial-'.uniqid(),
        'description' => 'Comercial',
    ]);

    return [$parentId, $subId];
}

function wadt_seed_root_category(string $name, ?int $deptId): int
{
    $id = \DB::table('solutions_category')->insertGetId([
        'parent_id' => 0,
        'priority' => 'low',
        'status' => 1,
        'visible' => 1,
        'department_id' => $deptId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('solutions_category_description')->insert([
        'category_id' => $id,
        'name' => $name,
        'permalink' => mb_strtolower($name).'-'.uniqid(),
        'description' => $name,
    ]);

    return $id;
}

describe('WhatsAppTicketService — Resolver de departamento', function () {

    it('usa category raiz Comercial (sem Atendimento) quando area_key=3', function () {
        // Cenário real: banco tem "Comercial" como categoria raiz com dept vinculado,
        // sem nenhuma categoria "Atendimento". Bug anterior retornava WHATSAPP_CATEGORY_DEFAULT
        // (que tinha dept Suporte), fazendo o chamado ir para Suporte.
        User::factory()->admin()->create();

        $comercialDept = Department::factory()->create(['name' => 'Comercial']);
        wadt_seed_root_category('Comercial', $comercialDept->id);

        $conv = WhatsAppConversation::factory()->create([
            'payload' => wadt_payload(['area_key' => '3', 'area_label' => 'Comercial']),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->department_id)->toBe($comercialDept->id)
            ->and($ticket->category_id)->not->toBeNull();
    });

    it('subcategoria com department vence o departamento configurado pelo area_key', function () {
        User::factory()->admin()->create();

        // Usamos nomes sem "Comercial" no department para garantir que o
        // lookup do canal (que faz LIKE %Comercial%) não pegue o mesmo id
        // — assim provamos de fato que a precedência da subcategoria atuou.
        $subDept = Department::factory()->create(['name' => 'Vendas Internas']);
        $channelDept = Department::factory()->create(['name' => 'Outro Setor']);

        wadt_seed_category_tree(null, $subDept->id);

        config([
            'whatsapp.chatbot.routing_departments.3' => $channelDept->id,
        ]);

        $conv = WhatsAppConversation::factory()->create([
            'payload' => wadt_payload(['area_key' => '3', 'area_label' => 'Outro Setor']),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->department_id)->toBe($subDept->id);
    });

    it('usa o area_key como canal quando categoria e subcategoria não têm department', function () {
        User::factory()->admin()->create();
        $channelFinanceiro = Department::factory()->create(['name' => 'Financeiro Canal Sozinho']);

        wadt_seed_category_tree(null, null);

        config([
            'whatsapp.chatbot.routing_departments.2' => $channelFinanceiro->id,
        ]);

        $conv = WhatsAppConversation::factory()->create([
            'payload' => wadt_payload(['area_key' => '2', 'area_label' => 'Financeiro']),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->department_id)->toBe($channelFinanceiro->id);
    });

    it('cai no fallback Suporte quando não há categoria, canal nem agente com department', function () {
        Department::query()->whereRaw('LOWER(name) like ?', ['%suporte%'])->delete();
        $support = Department::factory()->create(['name' => 'Suporte Técnico Fallback']);
        User::factory()->admin()->create(['department_id' => null]);

        wadt_seed_category_tree(null, null);

        $conv = WhatsAppConversation::factory()->create([
            'payload' => wadt_payload(['area_key' => '9', 'area_label' => 'Inexistente']),
        ]);

        $ticket = $this->service->createFromConversation($conv);

        expect($ticket->department_id)->toBe($support->id);
    });

});
