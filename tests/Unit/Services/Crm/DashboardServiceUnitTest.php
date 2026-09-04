<?php

/**
 * Testes UNITÁRIOS do DashboardService (CRM) — Repository mockado via interface.
 *
 * Estes testes isolam as regras de negócio do Service sem tocar o banco de dados.
 * O CrmDashboardRepositoryInterface é mockado para controlar retornos e verificar
 * que o Service delega as consultas e orquestra os dados corretamente.
 */

use App\Contracts\Repositories\CrmDashboardRepositoryInterface;
use App\Models\Crm\Feedback\Form;
use App\Services\Crm\DashboardService;
use App\Services\Crm\FeedbackService;
use Carbon\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ds_crm_service(): array
{
    $feedbackService = Mockery::mock(FeedbackService::class);
    $crmRepo         = Mockery::mock(CrmDashboardRepositoryInterface::class);

    $service = new DashboardService($feedbackService, $crmRepo);

    // Defaults silenciosos
    $feedbackService->shouldReceive('getNextFeedback')->byDefault()->andReturn(null);

    return [$service, $feedbackService, $crmRepo];
}

function ds_crm_form(int $id = 1, string $name = 'Formulário'): Form
{
    $form       = new Form();
    $form->id   = $id;
    $form->name = $name;
    return $form;
}

// ─── getDashboardData — sem formulários ───────────────────────────────────────

describe('DashboardService (CRM) — sem formulários cadastrados', function () {

    it('retorna array com coleções vazias quando não há formulários', function () {
        [$service, , $repo] = ds_crm_service();

        $repo->shouldReceive('getAllFormsOrdered')
            ->once()
            ->andReturn(collect());

        $result = $service->getDashboardData(null, null);

        expect($result['feedbacks'])->toBeEmpty()
            ->and($result['feedbacks_concluidos'])->toBeEmpty()
            ->and($result['customer'])->toBeNull()
            ->and($result['selected_form'])->toBeNull()
            ->and($result['forms'])->toBeEmpty();
    });

    it('não chama getPendingFeedbacks quando não há formulários', function () {
        [$service, , $repo] = ds_crm_service();

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect());
        $repo->shouldReceive('getPendingFeedbacks')->never();
        $repo->shouldReceive('getCompletedFeedbacks')->never();

        $service->getDashboardData(null, null);
    });

});

// ─── getDashboardData — com formulários ──────────────────────────────────────

describe('DashboardService (CRM) — com formulários', function () {

    it('seleciona o primeiro formulário quando formId é null', function () {
        [$service, , $repo] = ds_crm_service();

        $form1 = ds_crm_form(1, 'Form A');
        $form2 = ds_crm_form(2, 'Form B');

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form1, $form2]));
        $repo->shouldReceive('getPendingFeedbacks')->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $result = $service->getDashboardData(null, null);

        expect($result['selected_form']->id)->toBe(1);
    });

    it('seleciona o formulário pelo id informado', function () {
        [$service, , $repo] = ds_crm_service();

        $form1 = ds_crm_form(1, 'Form A');
        $form2 = ds_crm_form(2, 'Form B');

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form1, $form2]));
        $repo->shouldReceive('getPendingFeedbacks')->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $result = $service->getDashboardData(null, 2);

        expect($result['selected_form']->id)->toBe(2);
    });

    it('chama getPendingFeedbacks com o formId correto', function () {
        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(5);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')
            ->once()
            ->with(Mockery::type(Carbon::class), Mockery::type(Carbon::class), 5)
            ->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $service->getDashboardData(null, 5);
    });

    it('chama getCompletedFeedbacks com o formId correto', function () {
        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(7);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')
            ->once()
            ->with(7)
            ->andReturn(collect());

        $service->getDashboardData(null, 7);
    });

    it('inclui os feedbacks retornados pelo repository na resposta', function () {
        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(1);

        $pendingFeedbacks   = collect([new \App\Models\Crm\Feedback()]);
        $completedFeedbacks = collect([new \App\Models\Crm\Feedback(), new \App\Models\Crm\Feedback()]);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')->andReturn($pendingFeedbacks);
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn($completedFeedbacks);

        $result = $service->getDashboardData(null, 1);

        expect($result['feedbacks'])->toHaveCount(1)
            ->and($result['feedbacks_concluidos'])->toHaveCount(2);
    });

    it('delega a busca do próximo cliente ao FeedbackService', function () {
        [$service, $feedbackService, $repo] = ds_crm_service();

        $form = ds_crm_form(1);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $feedbackService->shouldReceive('getNextFeedback')
            ->once()
            ->andReturn(null);

        $service->getDashboardData(null, 1);
    });

    it('inclui start e end no array de retorno', function () {
        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(1);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $result = $service->getDashboardData(null, 1);

        expect($result)->toHaveKey('start')
            ->and($result)->toHaveKey('end')
            ->and($result['start'])->toBeInstanceOf(Carbon::class)
            ->and($result['end'])->toBeInstanceOf(Carbon::class);
    });

});

// ─── resolveDateRange — regra de negócio pura ────────────────────────────────

describe('DashboardService (CRM) — resolveDateRange (regras de negócio)', function () {

    it('formId=2 usa janela de ontem até hoje', function () {
        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(2);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')
            ->once()
            ->with(
                Mockery::on(fn (Carbon $start) => $start->isYesterday()),
                Mockery::on(fn (Carbon $end) => $end->isToday()),
                2
            )
            ->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $service->getDashboardData(null, 2);
    });

    it('sem startDateInput usa ontem como início (dias não-segunda)', function () {
        Carbon::setTestNow(Carbon::parse('2026-03-25')); // quarta-feira

        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(1);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')
            ->once()
            ->with(
                Mockery::on(fn (Carbon $start) => $start->format('Y-m-d') === '2026-03-24'),
                Mockery::type(Carbon::class),
                1
            )
            ->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $service->getDashboardData(null, 1);

        Carbon::setTestNow(); // reset
    });

    it('sem startDateInput em segunda-feira usa sexta anterior como início', function () {
        Carbon::setTestNow(Carbon::parse('2026-03-23')); // segunda-feira

        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(1);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')
            ->once()
            ->with(
                Mockery::on(fn (Carbon $start) => $start->format('Y-m-d') === '2026-03-20'),
                Mockery::type(Carbon::class),
                1
            )
            ->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        $service->getDashboardData(null, 1);

        Carbon::setTestNow(); // reset
    });

    it('com startDateInput limita a 10 dias quando diff > 10', function () {
        Carbon::setTestNow(Carbon::parse('2026-03-25')); // quarta-feira

        [$service, , $repo] = ds_crm_service();

        $form = ds_crm_form(1);

        $repo->shouldReceive('getAllFormsOrdered')->andReturn(collect([$form]));
        $repo->shouldReceive('getPendingFeedbacks')
            ->once()
            ->with(
                Mockery::on(fn (Carbon $start) => $start->format('Y-m-d') === '2026-03-15'),
                Mockery::type(Carbon::class),
                1
            )
            ->andReturn(collect());
        $repo->shouldReceive('getCompletedFeedbacks')->andReturn(collect());

        // startDateInput de 30 dias atrás → deve ser limitado a 10 dias
        $service->getDashboardData('23/02/2026', 1);

        Carbon::setTestNow(); // reset
    });

});
