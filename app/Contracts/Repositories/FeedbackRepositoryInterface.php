<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Element;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Crm\Feedback\Form;
use App\Models\Customer;
use App\Models\UserRating;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface FeedbackRepositoryInterface
{
    /**
     * Busca um Feedback pelo id; lança ModelNotFoundException se não encontrado.
     */
    public function findFeedback(int $id): Feedback;

    /**
     * Busca um Form pelo id; lança ModelNotFoundException se não encontrado.
     */
    public function findForm(int $id): Form;

    /**
     * Busca o primeiro Form cadastrado; retorna null se não houver nenhum.
     */
    public function firstForm(): ?Form;

    /**
     * Busca um Form pelo id, com fallback para o primeiro Form existente.
     * Retorna null se não houver nenhum Form cadastrado.
     */
    public function resolveForm(?int $formId): ?Form;

    /**
     * Cancela todos os feedbacks pendentes de um cliente (status pen ou open).
     */
    public function cancelPendingFeedbacksForCustomer(int $customerId): void;

    /**
     * Persiste (INSERT ou UPDATE) um Feedback no banco.
     */
    public function saveFeedback(Feedback $feedback): void;

    /**
     * Cancela um Feedback e apaga seus UserRatings de forma atômica.
     */
    public function cancelFeedbackWithRatings(int $feedbackId): void;

    /**
     * Retorna os IDs de company_id dos tickets completados no período, ordenados
     * por quantidade de tickets (DESC).
     *
     * @return SupportCollection<int, int>
     */
    public function getCandidateCustomerIds(Carbon $start, Carbon $end): SupportCollection;

    /**
     * Retorna os customer_ids com feedbacks em status pendente/open para o form.
     *
     * @param  SupportCollection<int, int>  $candidateIds
     * @return int[]
     */
    public function getBlockedCustomerIds(int $formId, SupportCollection $candidateIds): array;

    /**
     * Retorna os customer_ids que já tiveram feedback finalizado/cancelado no
     * período para o form (usado na validação estrita).
     *
     * @param  SupportCollection<int, int>  $candidateIds
     * @return int[]
     */
    public function getRecentlyProcessedCustomerIds(
        int $formId,
        Carbon $start,
        SupportCollection $candidateIds
    ): array;

    /**
     * Busca um Customer pelo id; retorna null se não encontrado.
     */
    public function findCustomer(int $customerId): ?Customer;

    /**
     * Retorna os ElementTypes de um Form, ordenados por sort_order.
     */
    public function getElementTypes(int $formId): Collection;

    /**
     * Cria ou atualiza um Element (resposta de um elemento dinâmico).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveElement(array $data): Element;

    /**
     * Cria ou atualiza um UserRating.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveUserRating(array $data): UserRating;

    /**
     * Retorna tickets da empresa concluídos num intervalo, com eager loading do agente.
     */
    public function getTicketsWithAgentByCompany(int $companyId, Carbon $start, Carbon $end): Collection;

    /**
     * Retorna todos os Customers ordenados por trade_name com os campos mínimos.
     */
    public function getAllCustomers(): Collection;

    /**
     * Agrega estatísticas de feedback por customer_id para um Form.
     * Retorna Collection agrupada por customer_id; cada item possui status e count.
     */
    public function getFeedbackStatsByForm(int $formId): \Illuminate\Support\Collection;

    /**
     * Agrega contagem de tickets por company_id num período.
     * Retorna Collection keyed por company_id => count.
     */
    public function getTicketCountsByPeriod(Carbon $start, Carbon $end): \Illuminate\Support\Collection;

    /**
     * Retorna um Feedback com relacionamentos customer e elements; lança
     * ModelNotFoundException se não encontrado.
     */
    public function findFeedbackWithRelations(int $id): Feedback;

    /**
     * Retorna os feedbacks recentes de um cliente para um Form, limitado a $limit.
     */
    public function getRecentFeedbacksForCustomer(int $customerId, int $formId, int $limit = 6): Collection;

    /**
     * Retorna os ElementTypes de um Form com campos específicos para a view de criação.
     */
    public function getElementTypesForView(int $formId): Collection;

    // =========================================================================
    // Admin CRM — gerenciamento de formulários e elementos
    // =========================================================================

    /**
     * Retorna todos os formulários de feedback ordenados por nome.
     */
    public function getAllForms(): Collection;

    /**
     * Cria um novo formulário de feedback.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForm(array $data): Form;

    /**
     * Atualiza um formulário existente e retorna a instância atualizada.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateForm(Form $form, array $data): Form;

    /**
     * Cria um novo ElementType de feedback.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFormElement(array $data): ElementType;

    /**
     * Atualiza um ElementType de feedback e retorna a instância atualizada.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateFormElement(ElementType $element, array $data): ElementType;

    /**
     * Remove um ElementType de feedback.
     */
    public function deleteFormElement(ElementType $element): bool;

    /**
     * Retorna estatísticas gerais dos formulários de feedback.
     *
     * @return array{total_forms: int, active_forms: int, total_elements: int}
     */
    public function getFeedbackFormStats(): array;

    /**
     * Retorna os ElementTypes de um formulário opcional, ordenados por sort_order.
     * Quando $formId é null, retorna todos os ElementTypes.
     */
    public function getFormElements(?int $formId = null): Collection;
}
