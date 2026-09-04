<?php

/**
 * Testes UNITÁRIOS — Agent\CommentService.
 * O TicketCommentRepositoryInterface é mockado; nenhum DB é tocado.
 */

use App\Contracts\Repositories\TicketCommentRepositoryInterface;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Comment;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Agent\CommentService;
use App\Services\Helpdesk\ChatTranscriptService;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function acs_repo(): MockInterface
{
    return Mockery::mock(TicketCommentRepositoryInterface::class);
}

function acs_transcript_service(): MockInterface
{
    return Mockery::mock(ChatTranscriptService::class);
}

function acs_service(MockInterface $repo, MockInterface $chatTranscriptService): CommentService
{
    return new CommentService($repo, $chatTranscriptService);
}

function acs_ticket(array $attrs = []): Ticket
{
    $ticket = new Ticket;
    foreach (array_merge(['id' => 1, 'status_id' => 1, 'agent_id' => null], $attrs) as $k => $v) {
        $ticket->$k = $v;
    }

    return $ticket;
}

function acs_comment(array $attrs = []): Comment
{
    $comment = new Comment;
    foreach (array_merge(['id' => 1, 'ticket_id' => 1, 'user_id' => 10, 'created_at' => now()], $attrs) as $k => $v) {
        $comment->$k = $v;
    }

    return $comment;
}

function acs_agent(bool $isAgent = true, bool $isAdmin = false): Agent
{
    $agent = Mockery::mock(Agent::class)->makePartial();
    $agent->shouldReceive('isAgent')->andReturn($isAgent);
    $agent->shouldReceive('isAdmin')->andReturn($isAdmin);

    return $agent;
}

// ─── addComment — agente ──────────────────────────────────────────────────────

describe('CommentService (unit) — addComment com agente', function () {

    it('cria o comentário e atualiza o ticket com status_reply_id e agent_id', function () {
        Config::set('ticketit.defaults.status_reply_id', 4);

        $repo = acs_repo();
        $ticket = acs_ticket(['id' => 1]);
        $comment = acs_comment(['created_at' => now()]);
        $agent = acs_agent(isAgent: true);
        $chatTranscriptService = acs_transcript_service();

        $repo->shouldReceive('createComment')
            ->once()
            ->with(Mockery::type('string'), 1, 10)
            ->andReturn($comment);

        $repo->shouldReceive('findAgent')
            ->once()
            ->with(10)
            ->andReturn($agent);

        $repo->shouldReceive('updateTicket')
            ->once()
            ->with($ticket, Mockery::on(fn ($attrs) => isset($attrs['status_id']) && $attrs['status_id'] === 4
                && isset($attrs['agent_id']) && $attrs['agent_id'] === 10
            ));

        $chatTranscriptService->shouldReceive('mirrorAgentComment')
            ->once()
            ->with($ticket, 10, 'Resposta do agente');

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 10;

        $result = acs_service($repo, $chatTranscriptService)->addComment($ticket, 'Resposta do agente', $user);

        expect($result)->toBeInstanceOf(Comment::class);
    });

    it('não define status_reply_id quando usuário não é agente nem admin', function () {
        $repo = acs_repo();
        $ticket = acs_ticket();
        $comment = acs_comment(['created_at' => now()]);
        $chatTranscriptService = acs_transcript_service();

        $repo->shouldReceive('createComment')->andReturn($comment);
        $repo->shouldReceive('findAgent')->andReturn(null);

        $repo->shouldReceive('updateTicket')
            ->once()
            ->with($ticket, Mockery::on(fn ($attrs) => ! isset($attrs['status_id'])));

        $chatTranscriptService->shouldNotReceive('mirrorAgentComment');

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 10;

        acs_service($repo, $chatTranscriptService)->addComment($ticket, 'Comentário de cliente', $user);
    });

    it('retorna o comentário criado pelo repositório', function () {
        $repo = acs_repo();
        $ticket = acs_ticket();
        $comment = acs_comment(['id' => 42]);
        $chatTranscriptService = acs_transcript_service();

        $repo->shouldReceive('createComment')->andReturn($comment);
        $repo->shouldReceive('findAgent')->andReturn(null);
        $repo->shouldReceive('updateTicket');
        $chatTranscriptService->shouldNotReceive('mirrorAgentComment');

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 10;
        $result = acs_service($repo, $chatTranscriptService)->addComment($ticket, 'Texto', $user);

        expect($result->id)->toBe(42);
    });

});
