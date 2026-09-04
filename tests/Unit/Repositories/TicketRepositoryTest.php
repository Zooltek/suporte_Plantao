<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketExtraCategory;
use App\Models\User;
use App\Repositories\TicketRepository;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function tr_company(array $attrs = []): Company
{
    return Company::factory()->create(array_merge([
        'is_active' => true,
        'financial_irregular' => false,
    ], $attrs));
}

function tr_cat_pair(): array
{
    $cat = Category::factory()->create(['parent_id' => 0]);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id]);

    return [$cat, $sub];
}

function tr_ticket(Company $company, Category $cat): Ticket
{
    $user = User::factory()->agent()->create();

    return Ticket::factory()->create([
        'company_id' => $company->id,
        'category_id' => $cat->category_id,
        'user_id' => $user->id,
        'status_id' => Ticket::STATUS_PENDING_ID,
        'priority_id' => 1,
    ]);
}

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('TicketRepository — findCategoryById', function () {

    it('retorna a categoria correta pelo category_id', function () {
        $cat = Category::factory()->create(['parent_id' => 0]);
        $repo = new TicketRepository;

        $found = $repo->findCategoryById($cat->category_id);

        expect($found)->not->toBeNull()
            ->and($found->category_id)->toBe($cat->category_id);
    });

    it('retorna null para category_id inexistente', function () {
        $repo = new TicketRepository;

        expect($repo->findCategoryById(999999))->toBeNull();
    });

});

describe('TicketRepository — findCompanyOrFail', function () {

    it('retorna a empresa pelo id', function () {
        $company = tr_company();
        $repo = new TicketRepository;

        $found = $repo->findCompanyOrFail($company->id);

        expect($found->id)->toBe($company->id);
    });

    it('lança ModelNotFoundException para empresa inexistente', function () {
        $repo = new TicketRepository;

        expect(fn () => $repo->findCompanyOrFail(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

describe('TicketRepository — save', function () {

    it('persiste o ticket no banco', function () {
        $company = tr_company();
        [$cat] = tr_cat_pair();
        $user = User::factory()->agent()->create();

        $ticket = new Ticket;
        $ticket->user_id = $user->id;
        $ticket->author_id = $user->id;
        $ticket->company_id = $company->id;
        $ticket->status_id = Ticket::STATUS_PENDING_ID;
        $ticket->priority_id = 1;
        $ticket->category_id = $cat->category_id;
        $ticket->subject = 'Teste save()';
        $ticket->content = 'conteúdo';
        $ticket->contact = 'CONTATO';

        (new TicketRepository)->save($ticket);

        expect($ticket->id)->not->toBeNull();
        $this->assertDatabaseHas('ticketit', ['id' => $ticket->id, 'subject' => 'Teste save()']);
    });

});

describe('TicketRepository — updateTicket', function () {

    it('atualiza atributos pontuais do ticket', function () {
        $user = User::factory()->agent()->create();
        test()->actingAs($user, 'admin'); // necessário para o TicketObserver registrar user_id

        $company = tr_company();
        [$cat] = tr_cat_pair();
        $ticket = tr_ticket($company, $cat);
        $repo = new TicketRepository;

        $repo->updateTicket($ticket, ['contact' => 'NOVO CONTATO']);

        $this->assertDatabaseHas('ticketit', [
            'id' => $ticket->id,
            'contact' => 'NOVO CONTATO',
        ]);
    });

});

describe('TicketRepository — syncExtraCategories', function () {

    it('apaga extras existentes e recria com os novos dados', function () {
        $company = tr_company();
        [$cat, $sub] = tr_cat_pair();
        [$cat2, $sub2] = tr_cat_pair();
        $ticket = tr_ticket($company, $cat);

        // Registra um extra antigo
        TicketExtraCategory::create([
            'ticket_id' => $ticket->id,
            'category_id' => $cat->category_id,
            'sub_category_id' => $sub->category_id,
        ]);

        $repo = new TicketRepository;
        $repo->syncExtraCategories($ticket, [
            ['category_id' => $cat2->category_id, 'sub_category_id' => $sub2->category_id],
        ]);

        // Extra antigo deve ter sido removido
        $this->assertDatabaseMissing('ticket_extra_categories', [
            'ticket_id' => $ticket->id,
            'category_id' => $cat->category_id,
        ]);

        // Novo extra deve existir
        $this->assertDatabaseHas('ticket_extra_categories', [
            'ticket_id' => $ticket->id,
            'category_id' => $cat2->category_id,
            'sub_category_id' => $sub2->category_id,
        ]);
    });

    it('com array vazio apenas limpa os extras existentes', function () {
        $company = tr_company();
        [$cat, $sub] = tr_cat_pair();
        $ticket = tr_ticket($company, $cat);

        TicketExtraCategory::create([
            'ticket_id' => $ticket->id,
            'category_id' => $cat->category_id,
            'sub_category_id' => $sub->category_id,
        ]);

        (new TicketRepository)->syncExtraCategories($ticket, []);

        expect(TicketExtraCategory::where('ticket_id', $ticket->id)->count())->toBe(0);
    });

    it('ignora entradas sem category_id ou sub_category_id', function () {
        $company = tr_company();
        [$cat] = tr_cat_pair();
        $ticket = tr_ticket($company, $cat);

        (new TicketRepository)->syncExtraCategories($ticket, [
            ['category_id' => 0,  'sub_category_id' => 5],  // catId zero → ignora
            ['category_id' => 99, 'sub_category_id' => 0],  // subId zero → ignora
        ]);

        expect(TicketExtraCategory::where('ticket_id', $ticket->id)->count())->toBe(0);
    });

});

describe('TicketRepository — updateAttachments', function () {

    it('reatribui attachments de um ticket temporário ao ticket definitivo', function () {
        $user = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $company = tr_company();
        [$cat] = tr_cat_pair();

        // Dois tickets: "temp" (onde attachment foi associado) e "destino"
        $tempTicket = tr_ticket($company, $cat);
        $destTicket = tr_ticket($company, $cat);

        // ticket_id é NOT NULL → attachment criado no ticket temporário
        $att = Attachment::create([
            'name' => 'arquivo.pdf',
            'original_name' => 'arquivo.pdf',
            'disk_path' => 'uploads/arquivo.pdf',
            'size' => 1024,
            'mime' => 'application/pdf',
            'author_id' => $user->id,
            'ticket_id' => $tempTicket->id,
            'status' => 0,
        ]);

        (new TicketRepository)->updateAttachments([$att->id], $destTicket->id);

        $this->assertDatabaseHas('ticketit_attachments', [
            'id' => $att->id,
            'ticket_id' => $destTicket->id,
        ]);
    });

    it('não executa query com array vazio', function () {
        $queryCount = 0;

        DB::listen(static function () use (&$queryCount) {
            $queryCount++;
        });

        (new TicketRepository)->updateAttachments([], 1);

        expect($queryCount)->toBe(0);
    });

});

describe('TicketRepository — getSolicitacaoStatusId', function () {

    it('retorna o id do status Solicitação', function () {
        Cache::forget('ticket.status_id.solicitacao');
        $status = Status::query()->updateOrCreate(
            ['id' => TicketStatusCatalog::REQUEST_ID],
            [
                'name' => 'Solicitação',
                'color' => '#8b5cf6',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => false,
            ]
        );

        $cached = (new TicketRepository)->getSolicitacaoStatusId();
        expect($cached)->toBe($status->id);
    });

    it('armazena o resultado em cache', function () {
        Cache::flush();
        $status = Status::query()->updateOrCreate(
            ['id' => TicketStatusCatalog::REQUEST_ID],
            [
                'name' => 'Solicitação',
                'color' => '#8b5cf6',
                'is_terminal' => false,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent' => false,
            ]
        );
        $repo = new TicketRepository;

        expect($repo->getSolicitacaoStatusId())->toBe($status->id);

        expect(Cache::has('ticket.status_id.solicitacao'))->toBeTrue();

        Status::query()->whereKey($status->id)->delete();

        // O contrato público normaliza o valor para int independentemente do
        // tipo escalar usado internamente pelo driver de cache.
        expect($repo->getSolicitacaoStatusId())->toBe($status->id);
    });

});

describe('TicketRepository — isStatusTerminal', function () {

    it('retorna true para status terminal', function () {
        $status = Status::factory()->terminal()->create();
        $repo = new TicketRepository;

        expect($repo->isStatusTerminal($status->id))->toBeTrue();
    });

    it('retorna false para status não-terminal', function () {
        $status = Status::factory()->create(['is_terminal' => false]);
        $repo = new TicketRepository;

        expect($repo->isStatusTerminal($status->id))->toBeFalse();
    });

});

describe('TicketRepository — isStatusRequiresSchedule', function () {

    it('retorna true para status que requer agendamento', function () {
        $status = Status::factory()->visitaTecnica()->create();
        $repo = new TicketRepository;

        expect($repo->isStatusRequiresSchedule($status->id))->toBeTrue();
    });

    it('retorna false para status sem exigência de agendamento', function () {
        $status = Status::factory()->create(['requires_schedule' => false]);
        $repo = new TicketRepository;

        expect($repo->isStatusRequiresSchedule($status->id))->toBeFalse();
    });

});

describe('TicketRepository — transaction', function () {

    it('executa o callback e retorna o valor', function () {
        $repo = new TicketRepository;
        $result = $repo->transaction(fn () => 'resultado');

        expect($result)->toBe('resultado');
    });

    it('faz rollback quando o callback lança exceção', function () {
        $company = tr_company();
        [$cat] = tr_cat_pair();

        expect(function () use ($company, $cat) {
            (new TicketRepository)->transaction(function () use ($company, $cat) {
                $user = User::factory()->agent()->create();
                $ticket = new Ticket;
                $ticket->user_id = $user->id;
                $ticket->author_id = $user->id;
                $ticket->company_id = $company->id;
                $ticket->status_id = Ticket::STATUS_PENDING_ID;
                $ticket->priority_id = 1;
                $ticket->category_id = $cat->category_id;
                $ticket->subject = 'Rollback test';
                $ticket->content = 'conteúdo';
                $ticket->contact = 'CONTATO';
                $ticket->save();

                throw new \RuntimeException('forçando rollback');
            });
        })->toThrow(\RuntimeException::class, 'forçando rollback');

        expect(Ticket::where('subject', 'Rollback test')->exists())->toBeFalse();
    });

});
