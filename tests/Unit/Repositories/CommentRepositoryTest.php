<?php

/**
 * Testes de integração — TicketCommentRepository & TaskCommentRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Models\Ticket\Comment as TicketComment;
use App\Models\Ticket\Ticket;
use App\Models\Tasks\Comment as TaskComment;
use App\Models\User;
use App\Repositories\TaskCommentRepository;
use App\Repositories\TicketCommentRepository;

// ─── TicketCommentRepository ──────────────────────────────────────────────────

describe('TicketCommentRepository — createComment', function () {

    it('persiste o comentário e retorna a instância', function () {
        $user   = User::factory()->agent()->create();
        $ticket = Ticket::factory()->create();

        $repo    = new TicketCommentRepository();
        $comment = $repo->createComment('Comentário de teste', $ticket->id, $user->id);

        expect($comment)->toBeInstanceOf(TicketComment::class)
            ->and($comment->id)->not->toBeNull()
            ->and($comment->ticket_id)->toBe($ticket->id)
            ->and($comment->user_id)->toBe($user->id);

        $this->assertDatabaseHas('ticketit_comments', [
            'id'        => $comment->id,
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
        ]);
    });

});

describe('TicketCommentRepository — updateTicket', function () {

    it('persiste os atributos fornecidos no ticket', function () {
        $user   = User::factory()->agent()->create();
        test()->actingAs($user, 'admin');

        $ticket = Ticket::factory()->create(['status_id' => 1]);

        $repo = new TicketCommentRepository();
        $repo->updateTicket($ticket, ['status_id' => 4]);

        $this->assertDatabaseHas('ticketit', [
            'id'        => $ticket->id,
            'status_id' => 4,
        ]);
    });

});

describe('TicketCommentRepository — findAgent', function () {

    it('retorna o Agent quando o usuário existe', function () {
        $user = User::factory()->agent()->create();
        $repo = new TicketCommentRepository();

        $agent = $repo->findAgent($user->id);

        expect($agent)->not->toBeNull()
            ->and($agent->id)->toBe($user->id);
    });

    it('retorna null para id inexistente', function () {
        $repo = new TicketCommentRepository();

        expect($repo->findAgent(999999))->toBeNull();
    });

});

// ─── TaskCommentRepository ────────────────────────────────────────────────────

describe('TaskCommentRepository — create', function () {

    it('cria e retorna o comentário de tarefa', function () {
        $user = User::factory()->create();
        $task = \App\Models\Tasks\Task::factory()->create();

        $repo    = new TaskCommentRepository();
        $comment = $repo->create([
            'content' => 'Comentário da tarefa',
            'task_id' => $task->id,
        ], $user->id);

        expect($comment)->toBeInstanceOf(TaskComment::class)
            ->and($comment->id)->not->toBeNull()
            ->and($comment->task_id)->toBe($task->id)
            ->and($comment->author_id)->toBe($user->id)
            ->and($comment->content)->toBe('Comentário da tarefa');

        $this->assertDatabaseHas('task_comments', [
            'id'        => $comment->id,
            'task_id'   => $task->id,
            'author_id' => $user->id,
        ]);
    });

});

describe('TaskCommentRepository — findOrFail', function () {

    it('retorna o comentário existente', function () {
        $user    = User::factory()->create();
        $task    = \App\Models\Tasks\Task::factory()->create();
        $comment = TaskComment::create([
            'content'   => 'Texto',
            'task_id'   => $task->id,
            'author_id' => $user->id,
        ]);

        $repo  = new TaskCommentRepository();
        $found = $repo->findOrFail($comment->id);

        expect($found->id)->toBe($comment->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        $repo = new TaskCommentRepository();

        expect(fn () => $repo->findOrFail(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

describe('TaskCommentRepository — delete', function () {

    it('remove o comentário do banco', function () {
        $user    = User::factory()->create();
        $task    = \App\Models\Tasks\Task::factory()->create();
        $comment = TaskComment::create([
            'content'   => 'Para deletar',
            'task_id'   => $task->id,
            'author_id' => $user->id,
        ]);

        $repo   = new TaskCommentRepository();
        $result = $repo->delete($comment);

        expect($result)->toBeTrue();
        $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
    });

});
