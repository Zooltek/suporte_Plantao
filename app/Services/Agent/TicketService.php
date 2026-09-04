<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\TicketRepositoryInterface;
use App\Exceptions\TicketBusinessException;
use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Ticket\Routing\TicketDepartmentResolver;
use App\Services\Ticket\Routing\TicketDepartmentRoutingIntent;
use App\Services\Ticket\Routing\TicketDepartmentTransferNotifier;
use App\Services\WhatsApp\WhatsAppBotReleaseService;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Support\Carbon;

class TicketService
{
    private readonly WhatsAppBotReleaseService $whatsAppBotReleaseService;

    private readonly TicketDepartmentResolver $departmentResolver;

    private readonly TicketDepartmentTransferNotifier $departmentTransferNotifier;

    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepository,
        private readonly TicketTechnicalContextService $technicalContextService,
        ?WhatsAppBotReleaseService $whatsAppBotReleaseService = null,
        ?TicketDepartmentResolver $departmentResolver = null,
        ?TicketDepartmentTransferNotifier $departmentTransferNotifier = null,
    ) {
        $this->whatsAppBotReleaseService = $whatsAppBotReleaseService
            ?? app(WhatsAppBotReleaseService::class);
        $this->departmentResolver = $departmentResolver
            ?? app(TicketDepartmentResolver::class);
        $this->departmentTransferNotifier = $departmentTransferNotifier
            ?? app(TicketDepartmentTransferNotifier::class);
    }

    /**
     * Salva (Cria ou Atualiza) um ticket com transação segura.
     *
     * OTIMIZAÇÃO N+1: categoria e empresa são buscadas UMA ÚNICA VEZ aqui e
     * repassadas para validateBusinessRules() e para o bloco de preenchimento,
     * eliminando as queries duplicadas que existiam nas versões anteriores.
     */
    public function saveTicket(Ticket $ticket, array $data, $user): Ticket
    {
        // Single fetch — elimina 2-3 queries duplicadas (N+1 fix)
        $category = $this->ticketRepository->findCategoryById((int) $data['category_id']);
        $sub = $this->ticketRepository->findCategoryById((int) $data['sub_category_id']);
        $company = $this->ticketRepository->findCompanyOrFail((int) $data['company_id']);
        $company->loadMissing('moduleTypes', 'scheduleModules');

        $this->validateBusinessRules($ticket, $data, $category, $sub, $company);

        return $this->ticketRepository->transaction(function () use ($ticket, $data, $user, $category, $company) {

            if (! $ticket->exists) {
                $ticket->author_id = $user->id;
                $ticket->created_at = Carbon::parse(data_get($data, 'created_at', now()));
            }

            $companyModuleId = ! empty($data['module_id']) ? (int) $data['module_id'] : null;
            $ratModuleId = $this->resolveRatModuleId(
                $company,
                $companyModuleId,
                ! empty($data['rat_module_id']) ? (int) $data['rat_module_id'] : null
            );

            $agentId = ! empty($data['agent_id']) ? (int) $data['agent_id'] : null;
            $requestedDepartmentId = ! empty($data['department_id']) ? (int) $data['department_id'] : null;
            $departmentId = $this->departmentResolver->resolve(new TicketDepartmentRoutingIntent(
                explicitDepartmentId: $requestedDepartmentId,
                subCategoryId: ! empty($data['sub_category_id']) ? (int) $data['sub_category_id'] : null,
                categoryId: (int) $category->category_id,
                agentId: $agentId,
            ));
            $statusId = $this->resolvePersistedStatusId(
                (int) $data['status_id'],
                $agentId
            );

            $ticket->agent_id = $agentId;
            $ticket->department_id = $departmentId;
            $ticket->company_id = $company->id;
            $ticket->status_id = $statusId;
            $ticket->referenced_ticket_id = ! empty($data['referenced_ticket_id']) ? (int) $data['referenced_ticket_id'] : null;
            $ticket->completed_at = $this->resolveCompletedAt($statusId);
            $ticket->priority_id = 1;
            $ticket->is_recurring = ! empty($data['is_recurring']);

            $ticket->category_id = (int) $category->category_id;
            $ticket->sub_category_id = (int) $data['sub_category_id'];
            $ticket->contact = strtoupper($data['contact']);
            $ticket->version = $this->technicalContextService->normalizeVersion(data_get($data, 'version'));
            $ticket->release = $this->technicalContextService->normalizeRelease(data_get($data, 'release'));
            $ticket->visible = 0;

            $catName = $category?->name ?? 'Chamado';
            $troubleContent = trim((string) data_get($data, 'trouble', ''));
            $ticket->subject = "{$catName} - ".strtoupper($data['contact']);
            $ticket->content = $troubleContent !== '' ? $troubleContent : 'Registro de Atendimento';
            $ticket->user_id = $user->id;

            $ticket->fill(collect($data)->only(['origin_id', 'trouble', 'solution', 'obs', 'elapsed_time'])->toArray());
            $ticket->module_id = $companyModuleId;
            $ticket->rat_module_id = $ratModuleId;
            $ticket->rat_responses = ! empty($data['rat_responses']) ? $data['rat_responses'] : null;

            $this->ticketRepository->save($ticket);

            $this->ticketRepository->syncExtraCategories($ticket, $data['extra_categories'] ?? []);
            $this->handlePostPersistActions($ticket, $data, $user);

            if ($ticket->completed_at !== null || $this->ticketRepository->isStatusTerminal((int) $ticket->status_id)) {
                try {
                    $this->whatsAppBotReleaseService->releaseTicketConversations($ticket, 'ticket_closed');
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[WhatsApp] Falha ao acionar releaseTicketConversations no saveTicket: '.$e->getMessage());
                }
            }

            return $ticket;
        });
    }

    /**
     * Atualiza apenas o status de um chamado existente (quick update).
     *
     * Regras:
     *  - Status terminal exige agente já atribuído ao chamado.
     *  - Status que requer agendamento: persiste e sinaliza redirect via retorno true.
     *
     * @throws \App\Exceptions\TicketBusinessException
     */
    public function quickUpdateStatus(Ticket $ticket, int $statusId): bool
    {
        if ($this->ticketRepository->isStatusTerminal($statusId)) {
            throw new TicketBusinessException(
                'Use o fluxo de fechamento para encerrar o chamado.'
            );
        }

        return $this->applyStatusTransition($ticket, $statusId);
    }

    /**
     * Atualiza apenas o agente de um chamado existente (quick update).
     *
     * Regra de domínio: chamado sem responsável volta para a fila de pendências.
     * Por isso, ao remover o agente, o status é revertido para Pendente e a
     * conclusão é limpa para evitar estados contraditórios no fluxo.
     */
    public function quickUpdateAgent(Ticket $ticket, ?int $agentId): void
    {
        $updates = ['agent_id' => $agentId];

        if (! $agentId) {
            $updates['status_id'] = Ticket::STATUS_PENDING_ID;
            $updates['completed_at'] = null;
        } else {
            $updates['department_id'] = $this->departmentResolver->resolve(new TicketDepartmentRoutingIntent(
                subCategoryId: $ticket->sub_category_id ? (int) $ticket->sub_category_id : null,
                categoryId: $ticket->category_id ? (int) $ticket->category_id : null,
                agentId: $agentId,
            ));
        }

        $this->ticketRepository->updateTicket($ticket, $updates);
    }

    /**
     * Atualiza apenas o departamento responsável do chamado (quick update).
     *
     * Recebe NULL para limpar a atribuição — o chamado fica visível a todos
     * os setores, retomando o comportamento "sem departamento". Cabe ao
     * controller verificar a Policy antes de invocar.
     */
    public function quickUpdateDepartment(Ticket $ticket, ?int $departmentId): void
    {
        $oldDepartmentId = $ticket->department_id ? (int) $ticket->department_id : null;

        $this->ticketRepository->updateTicket($ticket, ['department_id' => $departmentId]);

        $this->departmentTransferNotifier->notify($ticket->fresh(), $oldDepartmentId, $departmentId);
    }

    /**
     * Atualiza categoria e subcategoria do chamado (quick update).
     */
    public function quickUpdateCategory(Ticket $ticket, int $categoryId, ?int $subCategoryId = null): void
    {
        $this->ticketRepository->updateTicket($ticket, [
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
        ]);
    }

    /**
     * Atribui ou reatribui o chamado ao usuário informado.
     *
     * Mantém a semântica de "puxar para mim" em um único ponto de domínio,
     * evitando regra duplicada no controller e garantindo consistência entre
     * captura inicial e reatribuição entre agentes.
     *
     * @throws \App\Exceptions\TicketBusinessException
     */
    public function captureTicket(Ticket $ticket, User $user): void
    {
        $userId = (int) $user->id;

        if ((int) $ticket->agent_id === $userId) {
            throw new TicketBusinessException('Você já capturou o chamado.');
        }

        $this->quickUpdateAgent($ticket, $userId);
    }

    /**
     * Encerra um chamado com um status terminal explícito, reaproveitando
     * a mesma regra de transição usada pelo restante do workflow.
     *
     * @throws \App\Exceptions\TicketBusinessException
     */
    public function closeTicket(Ticket $ticket, int $statusId, ?string $solution = null): Ticket
    {
        if (! $this->ticketRepository->isStatusTerminal($statusId)) {
            throw new TicketBusinessException('Selecione um status de encerramento válido.');
        }

        $normalizedSolution = $this->ticketRepository->isStatusRequiresSolution($statusId)
            ? trim((string) $solution)
            : null;

        $this->applyStatusTransition($ticket, $statusId, [
            'solution' => $normalizedSolution,
        ]);

        $this->whatsAppBotReleaseService->releaseTicketConversations($ticket, 'ticket_closed');

        return $ticket->refresh();
    }

    /**
     * Gera e salva um token temporário para registro de tempo decorrido.
     */
    public function requestElapsedToken(Ticket $ticket): string
    {
        $token = hash('sha256', uniqid((string) $ticket->id, true));
        $this->ticketRepository->updateTicket($ticket, ['elapsed_token' => $token]);

        return $token;
    }

    // -------------------------------------------------------------------------
    // Privados — Regras de Negócio
    // -------------------------------------------------------------------------

    /**
     * Valida as regras de domínio do ticket.
     * Recebe entidades já carregadas para evitar queries extras (N+1 prevention).
     */
    private function validateBusinessRules(
        Ticket $ticket,
        array $data,
        ?Category $category,
        ?Category $sub,
        Company $company,
    ): void {
        $requestedStatusId = (int) $data['status_id'];

        // Status operacionais que representam atendimento ativo ou encerramento
        // exigem responsável explícito para evitar "downgrade" silencioso.
        if (Status::requiresAgent($requestedStatusId) && empty($data['agent_id'])) {
            throw new TicketBusinessException('O status selecionado exige um agente atribuído.');
        }

        // Valida vínculo hierárquico entre categoria e subcategoria
        if (! $category || ! $sub || $sub->parent_id != $category->category_id) {
            throw new TicketBusinessException('Vínculo entre Categoria e Sub-categoria inválido.');
        }

        if (! $company->isActive()) {
            throw new TicketBusinessException('Cliente inativo. Operação não permitida.');
        }

        $selectedModuleId = ! empty($data['module_id']) ? (int) $data['module_id'] : null;
        $selectedCompanyModule = $selectedModuleId !== null
            ? $company->moduleTypes->firstWhere('id', $selectedModuleId)
            : null;

        if ($selectedModuleId !== null && ! $selectedCompanyModule) {
            throw new TicketBusinessException('O módulo contratado selecionado não pertence a este cliente.');
        }

        $selectedRatModuleId = ! empty($data['rat_module_id']) ? (int) $data['rat_module_id'] : null;

        if (
            $selectedRatModuleId !== null &&
            empty($selectedCompanyModule?->rat_module_id) &&
            $company->scheduleModules->isNotEmpty() &&
            ! $company->scheduleModules->contains('id', $selectedRatModuleId)
        ) {
            throw new TicketBusinessException('O checklist RAT selecionado não está disponível para este cliente.');
        }

        // Bloqueia abertura de novos tickets para clientes inadimplentes
        if (! $ticket->exists && $company->financial_irregular) {
            $obs = trim((string) $company->observations);
            $msg = 'Cliente bloqueado por inadimplência.';
            if ($obs !== '') {
                $msg .= " Detalhe: {$obs}";
            }
            throw new TicketBusinessException($msg);
        }
    }

    private function resolveRatModuleId(Company $company, ?int $companyModuleId, ?int $selectedRatModuleId): ?int
    {
        if ($companyModuleId === null) {
            return null;
        }

        $linkedRatModuleId = $company->moduleTypes
            ->firstWhere('id', $companyModuleId)
            ?->rat_module_id;

        return $linkedRatModuleId ?: $selectedRatModuleId;
    }

    /**
     * Ações pós-persistência: vincula anexos e cria Task para "Solicitação".
     */
    private function handlePostPersistActions(Ticket $ticket, array $data, $user): void
    {
        if (! empty($data['attachments'])) {
            $this->ticketRepository->updateAttachments($data['attachments'], $ticket->id);
        }

        // Status Solicitação: cria Task vinculada se ainda não existir
        if ((int) $ticket->status_id === $this->ticketRepository->getSolicitacaoStatusId() && ! $ticket->task_id) {
            $task = $this->ticketRepository->createTask([
                'title' => 'Solicitação: '.$ticket->subject,
                'content' => $ticket->trouble ?? '',
                'status' => 'pen',
                'user_id' => $ticket->agent_id ?? $user->id,
                'author_id' => $user->id,
                'customer_id' => $ticket->company_id,
                'request_at' => now(),
            ]);

            $this->ticketRepository->updateTicket($ticket, ['task_id' => $task->id]);
        }
    }

    /**
     * Aplica uma transição de status respeitando as invariantes do domínio.
     *
     * @param  array<string, mixed>  $extraUpdates
     *
     * @throws \App\Exceptions\TicketBusinessException
     */
    private function applyStatusTransition(Ticket $ticket, int $statusId, array $extraUpdates = []): bool
    {
        $isTerminal = $this->ticketRepository->isStatusTerminal($statusId);
        $requiresSchedule = $this->ticketRepository->isStatusRequiresSchedule($statusId);

        if ($isTerminal && ! $ticket->hasAssignedAgent()) {
            throw new TicketBusinessException(
                'Um chamado finalizado precisa ter um agente atribuído.'
            );
        }

        if (
            $isTerminal &&
            $this->ticketRepository->isStatusRequiresSolution($statusId) &&
            trim((string) ($extraUpdates['solution'] ?? '')) === ''
        ) {
            throw new TicketBusinessException(
                'Informe a solução aplicada para encerrar como Resolvido.'
            );
        }

        $updates = array_merge($extraUpdates, [
            'status_id' => $statusId,
            'completed_at' => $this->resolveCompletedAt($statusId),
        ]);

        $this->ticketRepository->updateTicket($ticket, $updates);

        return $requiresSchedule && ! $isTerminal;
    }

    /**
     * Resolve o status efetivo persistido no formulário completo, mantendo a
     * regra do workflow em um único ponto para create/update.
     */
    private function resolvePersistedStatusId(int $requestedStatusId, ?int $agentId): int
    {
        if (Status::isRequestStatus($requestedStatusId)) {
            return $requestedStatusId;
        }

        if (! $agentId) {
            return Ticket::STATUS_PENDING_ID;
        }

        if (
            in_array($requestedStatusId, TicketStatusCatalog::preserveWithAgentIds(), true) ||
            $this->ticketRepository->isStatusTerminal($requestedStatusId) ||
            $this->ticketRepository->isStatusRequiresSchedule($requestedStatusId)
        ) {
            return $requestedStatusId;
        }

        return Ticket::STATUS_IN_PROGRESS_ID;
    }

    private function resolveCompletedAt(int $statusId): ?Carbon
    {
        return $this->ticketRepository->isStatusTerminal($statusId)
            ? now()
            : null;
    }
}
