<?php

declare(strict_types=1);

namespace App\Services\Crm;

use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Enums\FeedbackStatus;
use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Form;
use App\Models\Customer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function __construct(
        private readonly FeedbackRepositoryInterface $feedbackRepository,
    ) {}

    // =========================================================================
    // Criação / Cancelamento
    // =========================================================================

    /**
     * Cria e processa um novo feedback.
     *
     * @throws Exception
     */
    public function createFeedback(array $data, int $userId): Feedback
    {
        return DB::transaction(function () use ($data, $userId): Feedback {

            // 1. Resolve instância (verifica duplicação ou recupera pendente)
            $feedback = $this->resolveFeedbackInstance($data);

            // 2. Cancela feedbacks pendentes anteriores deste cliente
            $this->feedbackRepository->cancelPendingFeedbacksForCustomer((int) $data['customer_id']);

            // 3. Preenche e persiste
            $this->fillAndSaveFeedback($feedback, $data, $userId);

            // 4. Processa elementos dinâmicos (perguntas) e notas
            $this->processFeedbackElements($feedback, $data);

            return $feedback;
        });
    }

    public function cancelFeedback(int $feedbackId): void
    {
        $this->feedbackRepository->cancelFeedbackWithRatings($feedbackId);
    }

    // =========================================================================
    // Seleção do próximo cliente elegível
    // =========================================================================

    /**
     * Retorna o próximo cliente elegível para feedback no período.
     */
    public function getNextFeedback(Carbon $start, Carbon $end, Form $form, bool $flag): ?Customer
    {
        $candidateIds = $this->feedbackRepository->getCandidateCustomerIds($start, $end);

        if ($candidateIds->isEmpty()) {
            return null;
        }

        $blockedCustomerIds = $this->feedbackRepository->getBlockedCustomerIds($form->id, $candidateIds);

        // Em validação estrita, evita reapresentar cliente já finalizado no período.
        if ($flag === false) {
            $recentlyProcessed = $this->feedbackRepository->getRecentlyProcessedCustomerIds(
                $form->id,
                $start,
                $candidateIds
            );

            $blockedCustomerIds = array_unique(array_merge($blockedCustomerIds, $recentlyProcessed));
        }

        $selectedId = $candidateIds
            ->reject(fn (int $id) => in_array($id, $blockedCustomerIds, true))
            ->first();

        if (!$selectedId) {
            return null;
        }

        return $this->feedbackRepository->findCustomer((int) $selectedId);
    }

    // =========================================================================
    // Preparação de view
    // =========================================================================

    /**
     * Prepara os dados para a view de criação de feedback.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     * @throws Exception
     */
    public function prepareCreateViewData(array $input): array
    {
        $formId = isset($input['form_id']) ? (int) $input['form_id'] : null;
        $form   = $this->feedbackRepository->resolveForm($formId);

        if (!$form) {
            return ['isEmpty' => true];
        }

        $start    = $this->resolveStartDate($input['start'] ?? null);
        $feedback = null;

        $customers = $this->feedbackRepository->getAllCustomers();

        // Pré-carrega estatísticas de TODOS os clientes de forma eficiente
        $feedbackStats = $this->feedbackRepository->getFeedbackStatsByForm($form->id);
        $ticketStats   = $this->feedbackRepository->getTicketCountsByPeriod($start, Carbon::now());

        foreach ($customers as $cust) {
            $stats = $feedbackStats->get($cust->id, collect());

            $pending   = $stats->whereIn('status', [FeedbackStatus::PENDING->value, 'open'])->sum('count');
            $finalized = $stats->whereIn('status', [FeedbackStatus::FINISHED->value, '1'])->sum('count');
            $delay     = $stats->where('status', FeedbackStatus::CANCELED->value)->sum('count');

            $cust->setAttribute('remaining', $pending);
            $cust->setAttribute('finalized', $finalized);
            $cust->setAttribute('delay', $delay);
            $cust->setAttribute('total', $pending + $finalized + $delay);
            $cust->setAttribute('tickets_count', $ticketStats->get($cust->id, 0));
        }

        if (!empty($input['feedback_id'])) {
            $feedback = $this->feedbackRepository->findFeedbackWithRelations((int) $input['feedback_id']);

            if (!in_array($feedback->status, [FeedbackStatus::PENDING->value, 'open'], true)) {
                throw new Exception('Feedback já finalizado ou cancelado.');
            }

            $form     = $this->feedbackRepository->resolveForm($feedback->form_id) ?? $form;
            $customer = $feedback->customer;

            if (!$customers->find($customer->id)) {
                $customer->setAttribute('remaining', 0);
                $customer->setAttribute('finalized', 0);
                $customer->setAttribute('delay', 0);
                $customer->setAttribute('total', 0);
                $customer->setAttribute('tickets_count', $ticketStats->get($customer->id, 0));
            } else {
                $customer = $customers->find($customer->id);
            }
        } else {
            $customerId = isset($input['customer_id']) ? (int) $input['customer_id'] : null;
            $customer   = ($customerId ? $customers->firstWhere('id', $customerId) : null)
                ?? $this->getNextFeedback(
                    $start->copy()->startOfDay(),
                    Carbon::now()->subDay()->endOfDay(),
                    $form,
                    true
                );

            if ($customer && !$customers->find($customer->id)) {
                $customer->setAttribute('remaining', 0);
                $customer->setAttribute('finalized', 0);
                $customer->setAttribute('delay', 0);
                $customer->setAttribute('total', 0);
                $customer->setAttribute('tickets_count', $ticketStats->get($customer->id, 0));
            } elseif ($customer) {
                $customer = $customers->find($customer->id);
            }
        }

        if (!$customer instanceof Customer) {
            $customer = $customers->first();
        }

        if (!$customer) {
            return ['isEmpty' => true];
        }

        $elementTypes  = $this->feedbackRepository->getElementTypesForView((int) $form->id);
        $elementValues = $feedback
            ? $feedback->elements->pluck('value', 'element_id')
            : collect();

        $recentFeedbacks = $this->feedbackRepository->getRecentFeedbacksForCustomer(
            $customer->id,
            (int) $form->id
        );

        $ticketsCount = $customer->tickets_count ?? 0;

        return [
            'isEmpty'        => false,
            'feedback'       => $feedback,
            'customer'       => $customer,
            'customers'      => $customers,
            'form'           => $form,
            'start'          => $start,
            'tickets_count'  => $ticketsCount,
            'elementTypes'   => $elementTypes,
            'elementValues'  => $elementValues,
            'recentFeedbacks' => $recentFeedbacks,
        ];
    }

    // =========================================================================
    // Métodos privados de negócio (ZERO chamadas Eloquent aqui)
    // =========================================================================

    /**
     * Lógica principal para determinar se cria um novo Feedback ou usa existente.
     *
     * @throws Exception
     */
    private function resolveFeedbackInstance(array $data): Feedback
    {
        // Caso 1: Feedback ID já existe (finalizando um pendente)
        if (!empty($data['feedback_id'])) {
            $feedback = $this->feedbackRepository->findFeedback((int) $data['feedback_id']);

            if (!in_array($feedback->status, [FeedbackStatus::PENDING->value, 'open'], true)) {
                throw new Exception('Feedback duplicado ou inválido (Já finalizado).');
            }

            return $feedback;
        }

        // Caso 2: Validação de Regra de Negócio para Formulário Padrão (ID 1)
        $form = $this->feedbackRepository->findForm((int) $data['form_id']);

        if ((int) $form->id === 1) {
            $dates = $this->getStandardDateRange($data['start'] ?? null);

            $expectedCustomer = $this->getNextFeedback(
                $dates['start'],
                $dates['end'],
                $form,
                false
            );

            if ($expectedCustomer && (int) $expectedCustomer->id !== (int) $data['customer_id']) {
                throw new Exception(
                    'Feedback inválido: O cliente informado não corresponde ao agendamento automático.'
                );
            }
        }

        return new Feedback();
    }

    private function fillAndSaveFeedback(Feedback $feedback, array $data, int $userId): void
    {
        $feedback->fill([
            'suggestions' => $data['suggestions'] ?? null,
            'content'     => $data['content']     ?? null,
            'complaint'   => $data['complaint']   ?? null,
            'contact'     => $data['contact']     ?? null,
            'version'     => $data['version']     ?? null,
            'release'     => $data['release']     ?? null,
            'customer_id' => $data['customer_id'],
            'form_id'     => $data['form_id'],
        ]);

        $feedback->user_id      = $userId;
        $feedback->status       = FeedbackStatus::FINISHED->value;
        $feedback->completed_at = Carbon::now();

        $this->feedbackRepository->saveFeedback($feedback);
    }

    private function processFeedbackElements(Feedback $feedback, array $data): void
    {
        $elementTypes = $this->feedbackRepository->getElementTypes($feedback->form_id);

        foreach ($elementTypes as $type) {
            $value = $data[$type->name] ?? null;
            if (is_array($value)) {
                $value = implode(';', $value);
            }

            if ($value !== null) {
                $this->feedbackRepository->saveElement([
                    'feedback_id' => $feedback->id,
                    'element_id'  => $type->id,
                    'value'       => (string) $value,
                ]);
            }

            // Regra: Se for questão técnica, calcula nota para os agentes
            if ($type->name === 'question_tecnico' && $value !== null && is_numeric((string) $value)) {
                $this->syncUserRatings($feedback, (int) $value);
            }
        }
    }

    private function syncUserRatings(Feedback $feedback, int $score): void
    {
        $agents = $this->getAgentsRelatedToFeedback($feedback);

        $ratingValue = match ($score) {
            0       => 10,
            1       => 5,
            2       => 0,
            default => 5,
        };

        foreach ($agents as $agent) {
            $this->feedbackRepository->saveUserRating([
                'user_id'     => $agent->id,
                'feedback_id' => $feedback->id,
                'rating'      => $ratingValue,
            ]);
        }
    }

    /**
     * Busca agentes que atenderam tickets da empresa nos dias anteriores.
     * Regra: Se hoje é segunda, olha 3 dias atrás (Sexta). Se não, olha 1 dia atrás.
     *
     * @return array<int, mixed>
     */
    private function getAgentsRelatedToFeedback(Feedback $feedback): array
    {
        $now      = Carbon::now();
        $isMonday = $now->dayOfWeek === Carbon::MONDAY;

        $start = $isMonday
            ? $now->copy()->subDays(3)->startOfDay()
            : $now->copy()->subDay()->startOfDay();

        $end = $isMonday
            ? $now->copy()->subDays(3)->endOfDay()
            : $now->copy()->subDay()->endOfDay();

        return $this->feedbackRepository
            ->getTicketsWithAgentByCompany($feedback->customer_id, $start, $end)
            ->pluck('agent')
            ->filter()
            ->unique('id')
            ->all();
    }

    /**
     * Calcula o range de datas padrão para validação.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function getStandardDateRange(?string $startDateInput): array
    {
        $now = Carbon::now();

        // Data fim: Se segunda, volta 3 dias. Senão, 1 dia.
        $end = ($now->dayOfWeek === Carbon::MONDAY)
            ? $now->copy()->subDays(3)
            : $now->copy()->subDay();

        // Data inicio: Input do usuário ou agora
        $start = $startDateInput
            ? Carbon::createFromFormat('d/m/Y', $startDateInput)
            : Carbon::now();

        return ['start' => $start, 'end' => $end];
    }

    private function resolveStartDate(?string $startDateInput): Carbon
    {
        if (!$startDateInput) {
            return Carbon::now()->subDay();
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $startDateInput);
        } catch (Exception) {
            return Carbon::now()->subDay();
        }
    }
}
