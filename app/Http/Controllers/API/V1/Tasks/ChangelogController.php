<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\ChangelogResource;
use App\Http\Requests\API\Tasks\Changelog\ChangelogStoreRequest;
use App\Http\Requests\API\Tasks\Changelog\ChangelogIndexRequest;
use App\Services\ChangelogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OpenApi\Attributes as OA;
use Exception;

class ChangelogController extends Controller
{
    public function __construct(
        protected ChangelogService $changelogService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks/changelogs',
        summary: 'Listar itens de changelog pendentes',
        description: 'Retorna itens de changelog que ainda não foram vinculados a nenhuma versão.',
        operationId: 'listTaskChangelogs',
        tags: ['Changelog'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'project_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TaskChangelog'))
                ])
            )
        ]
    )]
    public function index(ChangelogIndexRequest $request): AnonymousResourceCollection
    {
        $changelogs = $this->changelogService->getPendingChangelogs(
            $request->project_id ? (int) $request->project_id : null
        );

        return ChangelogResource::collection($changelogs);
    }

    #[OA\Post(
        path: '/api/v1/tasks/changelogs',
        summary: 'Criar item de changelog',
        description: 'Cria um novo item de alteração. Requer permissão do departamento de TI.',
        operationId: 'storeTaskChangelog',
        tags: ['Changelog'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TaskChangelogRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Criado', content: new OA\JsonContent(ref: '#/components/schemas/TaskChangelog')),
            new OA\Response(response: 403, description: 'Acesso restrito ao TI')
        ]
    )]
    public function store(ChangelogStoreRequest $request): JsonResponse
    {
        try {
            $changelog = $this->changelogService->createChangelog(
                $request->validated(),
                Auth::user()
            );

            return (new ChangelogResource($changelog))->response()->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/tasks/changelogs/{id}',
        summary: 'Atualizar ou Reordenar item de changelog',
        description: 'Permite editar conteúdo ou alterar a posição (sort_order) do item.',
        operationId: 'updateTaskChangelog',
        tags: ['Changelog'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: '#/components/schemas/TaskChangelogRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado', content: new OA\JsonContent(ref: '#/components/schemas/TaskChangelog')),
            new OA\Response(response: 422, description: 'Não é possível editar versão fechada')
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'content'    => ['nullable', 'string', 'min:1'],
            'color'      => ['nullable', 'string', 'max:50'],
            'blank'      => ['nullable', 'boolean'],
            'bold'       => ['nullable', 'boolean'],
            'title'      => ['nullable', 'boolean'],
            'isSorting'  => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        try {
            $changelog = $this->changelogService->updateChangelog(
                $id,
                $validated,
                Auth::user(),
                $request->boolean('isSorting'),
                $request->has('sort_order') ? (int) $request->sort_order : null
            );

            return (new ChangelogResource($changelog))->response()->setStatusCode(200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[OA\Delete(
        path: '/api/v1/tasks/changelogs/{id}',
        summary: 'Remover item de changelog',
        operationId: 'deleteTaskChangelog',
        tags: ['Changelog'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Removido', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true)
            ]))
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->changelogService->deleteChangelog($id, Auth::user());
            return response()->json(['status' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/tasks/changelogs/report',
        summary: 'Gerar relatório de modificações',
        tags: ['Relatórios'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'report_id', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '1')),
            new OA\Parameter(name: 'project', in: 'query', description: 'ID do projeto', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'HTML do relatório', content: new OA\MediaType(mediaType: 'text/html'))
        ]
    )]
    public function report(Request $request): View|JsonResponse
    {
        return match ((string) $request->report_id) {
            '1' => $this->listaDeModificacao($request),
            default => response()->json(['error' => 'Report not found'], 404),
        };
    }

    private function listaDeModificacao(Request $request): View
    {
        $data = $this->changelogService->getReportDataForModificationList((int) $request->project);

        return view('tasks.report-general', $data);
    }
}