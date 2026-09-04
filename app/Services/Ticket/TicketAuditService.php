<?php

namespace App\Services\Ticket;

use App\Contracts\Repositories\TicketAuditRepositoryInterface;
use Illuminate\Support\Collection;

class TicketAuditService
{
    public function __construct(
        private readonly TicketAuditRepositoryInterface $repository,
    ) {}

    /**
     * Lista o histórico de auditoria de um ticket, do mais recente ao mais antigo.
     */
    public function listForTicket(int $ticketId): Collection
    {
        return $this->repository->allForTicket($ticketId);
    }
}
