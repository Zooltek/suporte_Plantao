<?php

namespace App\Repositories;

use App\Contracts\Repositories\CrmDashboardRepositoryInterface;
use App\Enums\FeedbackStatus;
use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Form;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CrmDashboardRepository implements CrmDashboardRepositoryInterface
{
    /**
     * Compatibilidade com dados legados ainda presentes na base.
     */
    private const PENDING_STATUSES = [
        FeedbackStatus::PENDING->value,
        'open',
    ];

    private const FINISHED_STATUSES = [
        FeedbackStatus::FINISHED->value,
        '1',
    ];

    /**
     * Retorna todos os formulários ordenados por nome.
     *
     * @return Collection<int, Form>
     */
    public function getAllFormsOrdered(): Collection
    {
        return Form::query()->orderBy('name')->get();
    }

    /**
     * Retorna os Feedbacks pendentes de um formulário no intervalo de datas,
     * com eager loading de customer para evitar N+1.
     * Fallback sem janela de datas quando não encontra registros no período.
     */
    public function getPendingFeedbacks(Carbon $start, Carbon $end, int $formId): Collection
    {
        $queryStart = $start->copy()->startOfDay();
        $queryEnd   = $end->copy()->endOfDay();

        $query = Feedback::query()
            ->with(['customer:id,trade_name'])
            ->whereIn('status', self::PENDING_STATUSES)
            ->where('form_id', $formId)
            ->whereBetween('created_at', [$queryStart, $queryEnd])
            ->where(function (Builder $query) use ($queryStart) {
                $query->whereNull('completed_at')
                      ->orWhere('completed_at', '>', $queryStart);
            })
            ->orderByDesc('updated_at');

        $feedbacks = $query->get();

        if ($feedbacks->isNotEmpty()) {
            return $feedbacks;
        }

        // Fallback sem janela de datas para não esconder registros válidos.
        return Feedback::query()
            ->with(['customer:id,trade_name'])
            ->whereIn('status', self::PENDING_STATUSES)
            ->where('form_id', $formId)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();
    }

    /**
     * Retorna os Feedbacks concluídos de um formulário respeitando as janelas
     * de return_at e completed_at, com eager loading de customer para evitar N+1.
     * Fallback sem recorte temporal quando não encontra registros.
     */
    public function getCompletedFeedbacks(int $formId): Collection
    {
        $threeDaysAgo = Carbon::now()->subDay(3)->endOfDay();
        $sevenDaysAgo = Carbon::now()->subDay(7)->endOfDay();

        $query = Feedback::query()
            ->with(['customer:id,trade_name'])
            ->whereIn('status', self::FINISHED_STATUSES)
            ->where('form_id', $formId)
            ->where(function (Builder $query) use ($threeDaysAgo) {
                $query->whereNull('return_at')
                      ->orWhere('return_at', '>', $threeDaysAgo);
            })
            ->where(function (Builder $query) use ($sevenDaysAgo) {
                $query->whereNull('completed_at')
                    ->orWhere('completed_at', '>', $sevenDaysAgo);
            })
            ->orderByDesc('updated_at');

        $feedbacks = $query->get();

        if ($feedbacks->isNotEmpty()) {
            return $feedbacks;
        }

        // Fallback sem recorte temporal para manter visibilidade de histórico legado.
        return Feedback::query()
            ->with(['customer:id,trade_name'])
            ->whereIn('status', self::FINISHED_STATUSES)
            ->where('form_id', $formId)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();
    }
}
