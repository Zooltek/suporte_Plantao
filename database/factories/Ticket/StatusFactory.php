<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\Status;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return $this->catalogState(TicketStatusCatalog::OPEN_ID);
    }

    public function terminal(): static
    {
        return $this->naoResolvido();
    }

    public function aberto(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::OPEN_ID));
    }

    public function emAndamento(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::IN_PROGRESS_ID));
    }

    public function visitaTecnica(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::TECHNICAL_VISIT_ID));
    }

    public function pendente(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::PENDING_ID));
    }

    public function naoResolvido(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::UNRESOLVED_ID));
    }

    public function solicitacao(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::REQUEST_ID));
    }

    public function requiresSolution(): static
    {
        return $this->state($this->catalogState(TicketStatusCatalog::RESOLVED_ID));
    }

    private function catalogState(int $statusId): array
    {
        $definition = TicketStatusCatalog::findById($statusId) ?? [];

        unset($definition['id']);

        return $definition;
    }
}
