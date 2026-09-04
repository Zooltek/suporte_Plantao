<?php

namespace App\Http\Controllers\Admin\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\API\V1\StoreUserRequest;
use App\Http\Requests\Admin\API\V1\UpdateUserRequest;
use App\Http\Resources\Admin\API\V1\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    #[OA\Get(
        path: '/admin/api/v1/users',
        summary: 'Listar todos os usuários',
        operationId: 'getUsersAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserSchema'))
                    ]
                )
            )
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $users = User::with('department')->get();
        return UserResource::collection($users);
    }

    #[OA\Post(
        path: '/admin/api/v1/users',
        summary: 'Criar novo usuário',
        operationId: 'storeUserAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Criado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuário criado com sucesso!'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserSchema')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->store($request->validated());
        return response()->json([
            'message' => 'Usuário criado com sucesso!',
            'user' => $user->load('department')
        ], 201);
    }

    #[OA\Get(
        path: '/admin/api/v1/users/{id}',
        summary: 'Obter detalhes de um usuário',
        operationId: 'showUserAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sucesso', content: new OA\JsonContent(ref: '#/components/schemas/UserSchema')),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('department'));
    }

    #[OA\Put(
        path: '/admin/api/v1/users/{id}',
        summary: 'Atualizar usuário',
        operationId: 'updateUserAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return response()->json([
            'message' => 'Usuário atualizado com sucesso!',
            'user' => $user->load('department'),
        ]);
    }

    #[OA\Put(
        path: '/admin/api/v1/users/{id}/password-reset',
        summary: 'Redefinir senha do usuário',
        operationId: 'resetPasswordUserAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200, 
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'A senha de João Silva foi redefinida.')
                    ]
                )
            )
        ]
    )]
    public function resetPassword(User $user): JsonResponse
    {
        $this->userService->resetPassword($user);

        return response()->json([
            'message' => "Um link de redefinição de senha foi enviado para o e-mail de {$user->name}.",
        ]);
    }

    #[OA\Get(
        path: '/admin/api/v1/users/{id}/deletion-preview',
        summary: 'Obter preview de vínculos antes de excluir usuário',
        operationId: 'deletionPreviewUserAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Preview gerado com sucesso')
        ]
    )]
    public function deletionPreview(User $user): JsonResponse
    {
        $preview = $this->userService->getDeletionPreview($user);

        return response()->json($preview);
    }

    #[OA\Delete(
        path: '/admin/api/v1/users/{id}',
        summary: 'Excluir usuário',
        operationId: 'destroyUserAdmin',
        tags: ['Admin - Usuários'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Excluído com sucesso'),
            new OA\Response(response: 422, description: 'Erro ao excluir usuário logado')
        ]
    )]
    public function destroy(\Illuminate\Http\Request $request, User $user): JsonResponse
    {
        $loggedUserId = auth('admin')->id() ?? auth()->id();

        if ($loggedUserId && (int) $loggedUserId === (int) $user->id) {
            return response()->json([
                'message' => 'Você não pode excluir o próprio usuário logado.',
            ], 422);
        }

        $transferToUserId = $request->input('transfer_to_user_id') ? (int) $request->input('transfer_to_user_id') : null;

        $result = $this->userService->anonymize($user, $transferToUserId);

        $message = 'Usuário excluído com sucesso.';
        if (!empty($result['transferred_items'])) {
            $message .= " ({$result['transferred_items']} pendências ativas transferidas com segurança).";
        }

        return response()->json([
            'message' => $message,
            'data' => $result,
        ]);
    }
}
