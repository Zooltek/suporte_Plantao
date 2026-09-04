<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\NotificationResource;
use App\Http\Requests\API\Tasks\NotificationStoreRequest;
use App\Services\API\V1\Tasks\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Exception;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks/notifications',
        summary: 'Listar notificações do usuário',
        description: 'Retorna as últimas 8 notificações. Pode filtrar apenas as não lidas da última hora usando active=true.',
        operationId: 'listTaskNotifications',
        tags: ['Notificações'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'active',
                in: 'query',
                description: 'Filtrar notificações não lidas e recentes',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TaskNotificationResource'))
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = auth('admin')->id() ?? Auth::id();

        $notifications = $this->notificationService->getNotifications(
            $userId,
            $request->boolean('active')
        );

        return NotificationResource::collection($notifications);
    }

    #[OA\Post(
        path: '/api/v1/tasks/notifications',
        summary: 'Criar notificação de tarefa',
        description: 'Cria uma nova notificação e dispara o Job de push com delay de 2s.',
        operationId: 'storeTaskNotification',
        tags: ['Notificações'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TaskNotificationResource')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Notificação criada'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(NotificationStoreRequest $request): JsonResponse
    {
        $notification = $this->notificationService->createNotification(
            $request->validated()
        );

        return response()->json([
            'message' => 'Notificação criada com sucesso!',
            'data'    => new NotificationResource($notification),
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/tasks/notifications/{id}/seen',
        summary: 'Marcar notificação como lida',
        operationId: 'markNotificationSeen',
        tags: ['Notificações'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sucesso'),
            new OA\Response(response: 422, description: 'Já visualizada')
        ]
    )]
    public function seen(int $id): JsonResponse
    {
        try {
            $this->notificationService->markAsSeen($id, auth('admin')->id() ?? Auth::id());
            return response()->json(['status' => true]);
        } catch (Exception $e) {
            $status = $e->getCode() === 422 ? 422 : 500;
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], $status);
        }
    }
}