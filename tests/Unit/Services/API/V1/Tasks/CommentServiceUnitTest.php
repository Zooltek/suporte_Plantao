<?php

/**
 * Testes UNITÁRIOS — API\V1\Tasks\CommentService.
 * O TaskCommentRepositoryInterface é mockado; nenhum DB é tocado.
 */

use App\Contracts\Repositories\TaskCommentRepositoryInterface;
use App\Models\Tasks\Comment;
use App\Services\API\V1\Tasks\CommentService;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function tcs_repo(): MockInterface
{
    return Mockery::mock(TaskCommentRepositoryInterface::class);
}

function tcs_service(MockInterface $repo): CommentService
{
    return new CommentService($repo);
}

function tcs_comment(array $attrs = []): Comment
{
    $comment = new Comment();
    foreach (array_merge(['id' => 1, 'user_id' => 5, 'task_id' => 1, 'content' => 'Texto'], $attrs) as $k => $v) {
        $comment->$k = $v;
    }
    return $comment;
}

// ─── createComment ────────────────────────────────────────────────────────────

describe('API\\Tasks\\CommentService (unit) — createComment', function () {

    it('delega a criação para o repositório e retorna o comentário', function () {
        $repo    = tcs_repo();
        $comment = tcs_comment(['id' => 7]);

        $repo->shouldReceive('create')
            ->once()
            ->with(['content' => 'Meu comentário', 'task_id' => 3], 5)
            ->andReturn($comment);

        $result = tcs_service($repo)->createComment(
            ['content' => 'Meu comentário', 'task_id' => 3],
            5
        );

        expect($result->id)->toBe(7);
    });

    it('repassa o userId corretamente ao repositório', function () {
        $repo = tcs_repo();

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::any(), 42)
            ->andReturn(tcs_comment());

        tcs_service($repo)->createComment(['content' => 'X', 'task_id' => 1], 42);
    });

});

// ─── deleteComment ────────────────────────────────────────────────────────────

describe('API\\Tasks\\CommentService (unit) — deleteComment', function () {

    it('delega a exclusão e retorna true quando o autor é o próprio', function () {
        $repo    = tcs_repo();
        $comment = tcs_comment(['user_id' => 5]);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($comment);
        $repo->shouldReceive('delete')->with($comment)->once()->andReturn(true);

        $result = tcs_service($repo)->deleteComment(1, 5);

        expect($result)->toBeTrue();
    });

    it('lança Exception 403 quando o usuário não é o autor', function () {
        $repo    = tcs_repo();
        $comment = tcs_comment(['user_id' => 5]);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($comment);
        $repo->shouldNotReceive('delete');

        expect(fn () => tcs_service($repo)->deleteComment(1, 99))
            ->toThrow(\Exception::class, 'Unauthorized');
    });

    it('não chama delete quando o usuário é diferente do autor', function () {
        $repo    = tcs_repo();
        $comment = tcs_comment(['user_id' => 10]);

        $repo->shouldReceive('findOrFail')->andReturn($comment);
        $repo->shouldNotReceive('delete');

        try {
            tcs_service($repo)->deleteComment(1, 999);
        } catch (\Exception) {
            // esperado
        }
    });

});
