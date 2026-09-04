<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\ScheduleRepositoryInterface;
use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Jobs\Agent\Schedule\SyncSchedule;
use App\Models\Schedule;
use App\Models\ScheduleType;
use App\Models\Ticket\Ticket;
use DomainException;
use Illuminate\Support\Carbon;

class ScheduleService
{
    public function __construct(
        private readonly ScheduleRepositoryInterface $repository,
        private readonly ScheduleTypeRepositoryInterface $scheduleTypeRepository,
    ) {}

    /**
     * Cria e sincroniza um novo agendamento.
     */
    public function createSchedule(array $data): Schedule
    {
        $schedule = new Schedule;

        $this->fillScheduleData($schedule, $data);

        $this->repository->save($schedule);

        $this->sync($schedule);

        return $schedule;
    }

    /**
     * Atualiza um agendamento existente validando as regras de estado.
     *
     * A autorização de acesso (quem pode editar) é responsabilidade da Policy.
     * Aqui apenas aplicamos as regras de negócio de estado.
     */
    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        if ($schedule->isFinalized() || $schedule->isCancelled()) {
            throw new DomainException('Não é possível alterar um agendamento finalizado ou desmarcado.');
        }

        if ($this->repository->hasActiveRecords($schedule->id)) {
            throw new DomainException('Não é possível alterar um agendamento com atividades vinculadas.');
        }

        $this->fillScheduleData($schedule, $data);

        $this->repository->save($schedule);

        $this->sync($schedule);

        return $schedule;
    }

    /**
     * Marca o agendamento como excluído (soft-delete via status='del').
     *
     * Regras de negócio:
     * - Não permite excluir agendamentos finalizados ou cancelados.
     * - Não permite excluir se houver RATs (records) vinculados.
     */
    public function deleteSchedule(Schedule $schedule): void
    {
        if ($schedule->isFinalized() || $schedule->isCancelled()) {
            throw new DomainException('Não é possível excluir um agendamento finalizado ou cancelado.');
        }

        if ($this->repository->hasActiveRecords($schedule->id)) {
            throw new DomainException('Não é possível excluir um agendamento com atividades (RATs) vinculadas.');
        }

        $schedule->status = 'del';

        $this->repository->save($schedule);

        $this->sync($schedule);
    }

    public function confirmSchedule(Schedule $schedule): Schedule
    {
        if ($schedule->isFinalized() || $schedule->isCancelled()) {
            throw new DomainException('Não é possível confirmar um agendamento finalizado ou cancelado.');
        }

        if (! $schedule->needsAdminConfirmation()) {
            throw new DomainException('Este agendamento não está aguardando confirmação do administrador.');
        }

        $schedule->requires_admin_confirmation = false;
        $schedule->status = 'con';

        $this->repository->save($schedule);

        $this->sync($schedule);

        return $schedule;
    }

    /**
     * Confirma um agendamento pendente — disponível para o próprio agente responsável.
     * Diferente de confirmSchedule (que é exclusivo do admin para agendamentos via ticket).
     */
    public function confirmScheduleByAgent(Schedule $schedule): Schedule
    {
        if ($schedule->isFinalized() || $schedule->isCancelled()) {
            throw new DomainException('Não é possível confirmar um agendamento finalizado ou cancelado.');
        }

        if (! $schedule->isPending()) {
            throw new DomainException('Apenas agendamentos pendentes podem ser confirmados.');
        }

        $schedule->status = 'con';

        $this->repository->save($schedule);

        $this->sync($schedule);

        return $schedule;
    }

    /**
     * Cancela um agendamento — disponível para o agente responsável ou admin.
     */
    public function cancelSchedule(Schedule $schedule): Schedule
    {
        if ($schedule->isFinalized() || $schedule->isCancelled()) {
            throw new DomainException('Não é possível cancelar um agendamento finalizado ou já cancelado.');
        }

        $schedule->status = 'can';

        $this->repository->save($schedule);

        $this->sync($schedule);

        return $schedule;
    }

    public function finalizeSchedule(Schedule $schedule): Schedule
    {
        if ($schedule->isFinalized() || $schedule->isCancelled()) {
            throw new DomainException('Não é possível finalizar um agendamento finalizado ou cancelado.');
        }

        if ($schedule->needsAdminConfirmation()) {
            throw new DomainException('Confirme o agendamento antes de finalizar o processo de implantação.');
        }

        if (! $this->repository->hasActiveRecords($schedule->id)) {
            throw new DomainException('Registre ao menos um RAT ativo antes de finalizar o agendamento.');
        }

        $schedule->requires_admin_confirmation = false;
        $schedule->status = 'fin';

        $this->repository->save($schedule);

        $this->sync($schedule);

        return $schedule;
    }

    /**
     * Preenche os dados comuns do agendamento.
     */
    private function fillScheduleData(Schedule $schedule, array $data): void
    {
        $sourceTicket = $this->resolveSourceTicket(data_get($data, 'ticket_id'));
        $kind = $sourceTicket ? Schedule::KIND_TICKET : data_get($data, 'kind', Schedule::KIND_CLIENT);
        $scheduleType = $this->scheduleTypeRepository->findBySlug($kind);

        // Tipos sem cliente (ex.: Reunião) não podem manter vínculo de cliente,
        // módulo ou contato — inclusive ao editar um agendamento que tinha cliente.
        $allowsCustomer = $sourceTicket !== null || $scheduleType === null || $scheduleType->requires_customer;

        $schedule->customer_id = $allowsCustomer ? (data_get($data, 'customer_id') ?: $sourceTicket?->company_id) : null;
        $schedule->agent_id = data_get($data, 'agent_id');
        $schedule->module_id = $allowsCustomer ? data_get($data, 'module_id') : null;
        $schedule->contact = $allowsCustomer ? (data_get($data, 'contact') ?: $sourceTicket?->contact) : null;
        $schedule->obs = data_get($data, 'obs');
        $schedule->ticket_id = $sourceTicket?->id;
        $schedule->kind = $kind;
        $schedule->schedule_type_id = $scheduleType?->id;
        $schedule->title = $this->resolveTitle($data, $sourceTicket, $scheduleType, $schedule->customer_id);

        $schedule->start_at = Carbon::parse($data['date'])
            ->setTimeFrom(Carbon::parse($data['start_hour']));

        if (! $schedule->exists && $sourceTicket) {
            $schedule->requires_admin_confirmation = true;
            $schedule->status = 'sch';

            return;
        }

        if ($schedule->needsAdminConfirmation()) {
            $schedule->status = 'sch';

            return;
        }

        $schedule->requires_admin_confirmation = false;

        if (! $schedule->exists || blank($schedule->status)) {
            $schedule->status = 'pen';
        }
    }

    private function resolveSourceTicket(?int $ticketId): ?Ticket
    {
        if (empty($ticketId)) {
            return null;
        }

        return $this->repository->findTicket($ticketId);
    }

    private function resolveTitle(array $data, ?Ticket $sourceTicket, ?ScheduleType $scheduleType, ?int $customerId): string
    {
        $title = trim((string) data_get($data, 'title', ''));

        if ($title !== '') {
            return $title;
        }

        if ($sourceTicket) {
            return "Visita técnica - Ticket #{$sourceTicket->id}";
        }

        if ($scheduleType?->requires_customer && $customerId) {
            return $scheduleType->label;
        }

        return $scheduleType?->label ?? 'Agendamento';
    }

    /**
     * Despacha o Job para a fila de forma isolada.
     */
    private function sync(Schedule $schedule): void
    {
        SyncSchedule::dispatch($schedule)->delay(now()->addSeconds(2));
    }
}
