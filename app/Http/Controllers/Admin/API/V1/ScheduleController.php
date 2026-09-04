<?php

namespace App\Http\Controllers\Admin\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ScheduleController extends Controller
{
    #[OA\Get(
        path: '/admin/api/v1/schedules',
        summary: 'Listar todos os agendamentos (Admin)',
        description: 'Retorna uma lista de todos os agendamentos do sistema',
        operationId: 'getSchedulesAdmin',
        tags: ['Admin - Agendamentos'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ScheduleResource'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index()
    {
        //
    }

    #[OA\Post(
        path: '/admin/api/v1/schedules',
        summary: 'Criar novo agendamento (Admin)',
        operationId: 'storeScheduleAdmin',
        tags: ['Admin - Agendamentos'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ScheduleRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criado com sucesso'),
            new OA\Response(response: 400, description: 'Dados inválidos')
        ]
    )]
    public function store(Request $request)
    {
        //
    }

    #[OA\Get(
        path: '/admin/api/v1/schedules/{id}',
        summary: 'Obter agendamento específico (Admin)',
        operationId: 'getScheduleAdmin',
        tags: ['Admin - Agendamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/ScheduleResource')
            ),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function show(string $id)
    {
        //
    }

    #[OA\Put(
        path: '/admin/api/v1/schedules/{id}',
        summary: 'Atualizar agendamento (Admin)',
        operationId: 'updateScheduleAdmin',
        tags: ['Admin - Agendamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ScheduleRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado com sucesso'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function update(Request $request, string $id)
    {
        //
    }

    #[OA\Delete(
        path: '/admin/api/v1/schedules/{id}',
        summary: 'Excluir agendamento (Admin)',
        operationId: 'deleteScheduleAdmin',
        tags: ['Admin - Agendamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Excluído com sucesso'),
            new OA\Response(response: 404, description: 'Não encontrado')
        ]
    )]
    public function destroy(string $id)
    {
        //
    }
}