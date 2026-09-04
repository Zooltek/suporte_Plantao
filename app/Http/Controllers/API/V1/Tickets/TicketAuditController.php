<?php

namespace App\Http\Controllers\API\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tickets\TicketAuditResource;
use App\Services\Ticket\TicketAuditService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketAuditController extends Controller
{
    use AuthorizesTicketAccess;

    public function __construct(
        private readonly TicketAuditService $auditService
    ) {}

    public function index(int $ticket): AnonymousResourceCollection
    {
        $ticket = $this->authorizeTicketAccess($ticket);

        return TicketAuditResource::collection(
            $this->auditService->listForTicket($ticket->id)
        );
    }
}
