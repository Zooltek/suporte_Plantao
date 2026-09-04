<?php

namespace App\Http\Controllers\API\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tickets\TicketIssueStoreRequest;
use App\Http\Requests\API\V1\Tickets\TicketIssueResolveRequest;
use App\Http\Resources\API\V1\Tickets\TicketIssueResource;
use App\Models\Ticket\TicketIssue;
use App\Services\Ticket\TicketIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketIssueController extends Controller
{
    use AuthorizesTicketAccess;

    public function __construct(
        private readonly TicketIssueService $issueService
    ) {}

    public function index(int $ticket): AnonymousResourceCollection
    {
        $ticket = $this->authorizeTicketAccess($ticket);

        return TicketIssueResource::collection(
            $this->issueService->listForTicket($ticket->id)
        );
    }

    public function store(TicketIssueStoreRequest $request, int $ticket): JsonResponse
    {
        $ticket = $this->authorizeTicketAccess($ticket);
        $issue = $this->issueService->create($ticket->id, $request->validated());

        return (new TicketIssueResource($issue->load(['creator', 'resolver'])))
            ->response()
            ->setStatusCode(201);
    }

    public function resolve(TicketIssueResolveRequest $request, int $ticket, TicketIssue $issue): JsonResponse
    {
        $ticket = $this->authorizeTicketAccess($ticket);

        abort_if(
            (int) $issue->ticket_id !== (int) $ticket->id,
            404,
            'Problema não encontrado para este ticket.'
        );

        $issue = $this->issueService->resolve($issue, $request->validated('solution'));

        return (new TicketIssueResource($issue))->response();
    }

    public function destroy(int $ticket, TicketIssue $issue): JsonResponse
    {
        $ticket = $this->authorizeTicketAccess($ticket);

        abort_if(
            (int) $issue->ticket_id !== (int) $ticket->id,
            404,
            'Problema não encontrado para este ticket.'
        );

        $this->issueService->delete($issue);

        return response()->json(['message' => 'Problema removido.']);
    }
}
