<?php

namespace App\Services\Crm;

use App\Contracts\Repositories\CrmDashboardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        protected FeedbackService                  $feedbackService,
        protected CrmDashboardRepositoryInterface  $crmDashboardRepository,
    ) {}

    public function getDashboardData(?string $startDateInput, ?int $formId): array
    {
        // 1. Resolvemos as datas (extraímos para um método privado para limpar o código)
        [$start, $end] = $this->resolveDateRange($startDateInput, $formId);

        // 2. Definimos qual formulário está selecionado (via Repository)
        $forms        = $this->crmDashboardRepository->getAllFormsOrdered();
        $selectedForm = $formId
            ? $forms->firstWhere('id', $formId)
            : $forms->first();

        if (!$selectedForm) {
            return [
                'feedbacks'            => collect(),
                'feedbacks_concluidos' => collect(),
                'start'                => $start,
                'end'                  => $end,
                'customer'             => null,
                'forms'                => $forms,
                'selected_form'        => null,
            ];
        }

        // Fallback para garantir que o ID esteja correto caso não venha na request
        $formId = $selectedForm->id;

        // 3. Buscamos os Feedbacks Pendentes (via Repository)
        $feedbacks = $this->crmDashboardRepository->getPendingFeedbacks($start, $end, $formId);

        // 4. Buscamos o "Próximo Cliente" (via FeedbackService)
        $customer = $this->feedbackService->getNextFeedback(
            $start->copy()->startOfDay(),
            Carbon::now()->subDay(1)->endOfDay(),
            $selectedForm,
            true
        );

        // 5. Buscamos os Feedbacks Concluídos (via Repository)
        $feedbacksConcluidos = $this->crmDashboardRepository->getCompletedFeedbacks($formId);

        return [
            'feedbacks'            => $feedbacks,
            'feedbacks_concluidos' => $feedbacksConcluidos,
            'start'                => $start,
            'end'                  => $end,
            'customer'             => $customer,
            'forms'                => $forms,
            'selected_form'        => $selectedForm,
        ];
    }

    /**
     * Lógica complexa de datas isolada aqui.
     */
    private function resolveDateRange(?string $startDateInput, ?int $formId): array
    {
        $now = Carbon::now();

        // Regra específica do Form ID 2
        if ($formId == 2) {
            return [
                $now->copy()->subDay(1)->startOfDay(),
                $now->copy()->endOfDay(),
            ];
        }

        if ($startDateInput) {
            $start = Carbon::createFromFormat('d/m/Y', $startDateInput);
            $end   = $now->copy();

            if ($start->diffInDays($end) > 10) {
                $start = $now->copy()->subDays(10);
            }

            // Regra de Segunda-feira (Fim de semana)
            $end = ($end->dayOfWeek == 1) ? $end->subDay(3) : $now->endOfDay();
        } else {
            $start = $now->copy();
            $end   = $now->copy();

            // Regra de Segunda-feira para data inicial padrão
            $start = ($start->dayOfWeek == 1) ? $start->subDay(3) : $start->subDay(1);
        }

        return [$start, $end];
    }
}
