<?php

namespace App\Contracts\Repositories;

use App\Models\Crm\Feedback\Form;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CrmDashboardRepositoryInterface
{
    /**
     * Retorna todos os formulários ordenados por nome.
     *
     * @return Collection<int, Form>
     */
    public function getAllFormsOrdered(): Collection;

    /**
     * Retorna os Feedbacks pendentes (status pending/open) de um formulário
     * no intervalo de datas fornecido, com eager loading de customer.
     * Quando não encontra registros no período, faz fallback sem restrição de data
     * (limitado a 100 registros).
     */
    public function getPendingFeedbacks(Carbon $start, Carbon $end, int $formId): Collection;

    /**
     * Retorna os Feedbacks concluídos de um formulário respeitando as janelas
     * de return_at e completed_at, com eager loading de customer.
     * Quando não encontra registros, faz fallback sem restrição temporal
     * (limitado a 100 registros).
     */
    public function getCompletedFeedbacks(int $formId): Collection;
}
