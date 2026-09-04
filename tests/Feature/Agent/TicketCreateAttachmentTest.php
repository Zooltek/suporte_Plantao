<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(StatusSeeder::class);
    Storage::fake('public');
});

function tca_cat_pair(): array
{
    $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $child = Category::factory()->create(['parent_id' => $parent->category_id, 'priority' => 'low']);

    return [$parent, $child];
}

function tca_payload(Company $company, array $cats, Status $status, int $agentId): array
{
    [$parent, $child] = $cats;

    return [
        'company_id' => $company->id,
        'status_id' => $status->id,
        'category_id' => $parent->category_id,
        'sub_category_id' => $child->category_id,
        'contact' => 'JOÃO ANEXO',
        'agent_id' => $agentId,
        'trouble' => 'Cliente com erro de impressão',
        'solution' => 'Resolvido em campo',
    ];
}

describe('TicketsController — anexos na abertura do chamado', function () {

    it('persiste cada arquivo enviado via campo arquivo[] vinculado ao ticket criado', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create();
        $cats = tca_cat_pair();

        $files = [
            UploadedFile::fake()->image('print1.png'),
            UploadedFile::fake()->create('relatorio.pdf', 12, 'application/pdf'),
        ];

        $payload = tca_payload($company, $cats, $status, $agent->id) + ['arquivo' => $files];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertRedirect(route('agent.ticket.index'));

        $ticket = Ticket::where('company_id', $company->id)->latest()->firstOrFail();

        expect(Attachment::where('ticket_id', $ticket->id)->count())->toBe(2);

        Attachment::where('ticket_id', $ticket->id)->get()->each(function (Attachment $attachment) {
            Storage::disk('public')->assertExists($attachment->disk_path);
        });
    });

    it('mantém o ticket persistido mesmo quando nenhum arquivo é enviado', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create();
        $cats = tca_cat_pair();

        $this->post(route('agent.ticket.store'), tca_payload($company, $cats, $status, $agent->id))
            ->assertRedirect(route('agent.ticket.index'));

        $ticket = Ticket::where('company_id', $company->id)->latest()->firstOrFail();

        expect(Attachment::where('ticket_id', $ticket->id)->count())->toBe(0);
    });

    it('rejeita uploads acima do limite configurado e não cria o chamado', function () {
        $agent = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create();
        $cats = tca_cat_pair();

        $oversize = UploadedFile::fake()->create('grande.bin', 25 * 1024, 'application/octet-stream');

        $payload = tca_payload($company, $cats, $status, $agent->id) + ['arquivo' => [$oversize]];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertSessionHasErrors(['arquivo.0']);

        expect(Ticket::where('company_id', $company->id)->count())->toBe(0);
    });

});
