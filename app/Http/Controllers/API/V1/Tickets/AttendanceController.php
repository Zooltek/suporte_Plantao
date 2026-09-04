<?php

namespace App\Http\Controllers\API\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tickets\AttendanceStoreRequest;
use App\Http\Resources\API\V1\Tickets\AttendanceResource;
use App\Models\Ticket\Ticket;
use App\Services\API\V1\Tickets\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/tickets/{ticket}/attendances",
     *     summary="Listar atendimentos de um ticket",
     *     description="Retorna uma lista de atendimentos de um ticket específico",
     *     tags={"Ticket Attendances"},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID do ticket",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de atendimentos retornada com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/AttendanceResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Ticket não encontrado"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function index(int $ticket): AnonymousResourceCollection
    {
        $ticket = $this->authorizeAttendanceRead($ticket);

        return AttendanceResource::collection(
            $this->attendanceService->listForTicket($ticket->id)
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tickets/{ticket}/attendances",
     *     summary="Criar novo atendimento",
     *     description="Cria um novo atendimento para um ticket específico",
     *     tags={"Ticket Attendances"},
     *     @OA\Parameter(
     *         name="ticket",
     *         in="path",
     *         description="ID do ticket",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="notes",      type="string",  example="Cliente solicitou suporte técnico"),
     *             @OA\Property(property="return_zap", type="boolean", example=true),
     *             @OA\Property(property="return_tel", type="boolean", example=false),
     *             @OA\Property(property="return_cel", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Atendimento criado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", ref="#/components/schemas/AttendanceResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Ticket não encontrado"),
     *     @OA\Response(response=422, description="Dados de validação inválidos"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function store(AttendanceStoreRequest $request, int $ticket): JsonResponse
    {
        $ticket = $this->authorizeAttendanceWrite($ticket);

        $attendance = $this->attendanceService->create($ticket->id, $request->validated());

        return (new AttendanceResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    private function authorizeAttendanceRead(int $ticketId): Ticket
    {
        $ticket = Ticket::query()->findOrFail($ticketId);
        $this->authorize('view', $ticket);

        return $ticket;
    }

    private function authorizeAttendanceWrite(int $ticketId): Ticket
    {
        $ticket = $this->authorizeAttendanceRead($ticketId);
        $user = Auth::guard('admin')->user() ?? Auth::user();

        if ($user?->ticketit_admin) {
            return $ticket;
        }

        if ($user?->ticketit_agent && $ticket->isQueuePending()) {
            return $ticket;
        }

        $this->authorize('update', $ticket);

        return $ticket;
    }
}
