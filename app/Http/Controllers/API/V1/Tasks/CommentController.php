<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\CommentResource;
use App\Http\Requests\API\Tasks\CommentStoreRequest;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Exception;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService
    ) {}

    #[OA\Post(
        path: '/api/v1/tasks/comments',
        summary: 'Adicionar comentário em uma task',
        operationId: 'storeTaskComment',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TaskCommentRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Comentário criado com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/TaskCommentResource')
            ),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function store(CommentStoreRequest $request): JsonResponse
    {
        $comment = $this->commentService->createComment(
            $request->validated(), 
            Auth::id()
        );

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/tasks/comments/{id}',
        summary: 'Excluir um comentário',
        description: 'Remove um comentário do sistema. Somente o autor do comentário pode excluí-lo.',
        operationId: 'deleteTaskComment',
        tags: ['Comments'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID do comentário',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Comentário excluído'),
            new OA\Response(
                response: 403, 
                description: 'Não autorizado (Você não é o autor deste comentário)',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Unauthorized')
                ])
            ),
            new OA\Response(response: 404, description: 'Comentário não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->commentService->deleteComment($id, Auth::id());
            return response()->json(null, 204);
        } catch (Exception $e) {
            $status = $e->getCode() === 403 ? 403 : 500;
            return response()->json(['error' => $e->getMessage()], $status);
        }
    }
}