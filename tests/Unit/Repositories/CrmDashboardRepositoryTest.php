<?php

/**
 * Testes de integração do CrmDashboardRepository — SQLite in-memory.
 *
 * Cobrem todos os métodos públicos da implementação, validando que as queries
 * produzem os resultados esperados sem acionar lógica de negócio do Service.
 */

use App\Enums\FeedbackStatus;
use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Form;
use App\Models\Customer;
use App\Repositories\CrmDashboardRepository;
use Carbon\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cdr_repo(): CrmDashboardRepository
{
    return new CrmDashboardRepository();
}

function cdr_form(string $name = 'Formulário Teste'): Form
{
    return Form::factory()->create(['name' => $name]);
}

function cdr_customer(): Customer
{
    return Customer::factory()->create();
}

function cdr_feedback(Form $form, Customer $customer, string $status, array $attrs = []): Feedback
{
    return Feedback::factory()->create(array_merge([
        'form_id'     => $form->id,
        'customer_id' => $customer->id,
        'status'      => $status,
    ], $attrs));
}

// ─── getAllFormsOrdered ────────────────────────────────────────────────────────

describe('CrmDashboardRepository — getAllFormsOrdered', function () {

    it('retorna todos os formulários ordenados por nome', function () {
        cdr_form('Zebra Form');
        cdr_form('Alpha Form');
        cdr_form('Mango Form');

        $forms = cdr_repo()->getAllFormsOrdered();

        expect($forms->pluck('name')->all())->toBe(['Alpha Form', 'Mango Form', 'Zebra Form']);
    });

    it('retorna coleção vazia quando não há formulários', function () {
        $forms = cdr_repo()->getAllFormsOrdered();

        expect($forms)->toBeEmpty();
    });

    it('retorna instâncias de Form', function () {
        cdr_form('Form A');

        $forms = cdr_repo()->getAllFormsOrdered();

        expect($forms->first())->toBeInstanceOf(Form::class);
    });

});

// ─── getPendingFeedbacks ───────────────────────────────────────────────────────

describe('CrmDashboardRepository — getPendingFeedbacks', function () {

    it('retorna feedbacks com status pending no período', function () {
        $form     = cdr_form();
        $customer = cdr_customer();
        $now      = Carbon::now();

        $pending = cdr_feedback($form, $customer, FeedbackStatus::PENDING->value, [
            'created_at' => $now->copy()->subHour(),
        ]);

        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDay(),
            $now->copy()->addDay(),
            $form->id
        );

        expect($result->pluck('id'))->toContain($pending->id);
    });

    it('retorna feedbacks com status legado "open"', function () {
        $form     = cdr_form();
        $customer = cdr_customer();
        $now      = Carbon::now();

        $open = cdr_feedback($form, $customer, 'open', [
            'created_at' => $now->copy()->subHour(),
        ]);

        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDay(),
            $now->copy()->addDay(),
            $form->id
        );

        expect($result->pluck('id'))->toContain($open->id);
    });

    it('não retorna feedbacks de outro formulário', function () {
        $form1    = cdr_form('Form 1');
        $form2    = cdr_form('Form 2');
        $customer = cdr_customer();
        $now      = Carbon::now();

        cdr_feedback($form1, $customer, FeedbackStatus::PENDING->value, [
            'created_at' => $now->copy()->subHour(),
        ]);

        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDay(),
            $now->copy()->addDay(),
            $form2->id
        );

        expect($result)->toBeEmpty();
    });

    it('não retorna feedbacks com status finished', function () {
        $form     = cdr_form();
        $customer = cdr_customer();
        $now      = Carbon::now();

        cdr_feedback($form, $customer, FeedbackStatus::FINISHED->value, [
            'created_at' => $now->copy()->subHour(),
        ]);

        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDay(),
            $now->copy()->addDay(),
            $form->id
        );

        expect($result)->toBeEmpty();
    });

    it('usa fallback (sem janela de datas) quando não há registros no período', function () {
        $form     = cdr_form();
        $customer = cdr_customer();
        $now      = Carbon::now();

        // Cria feedback fora do período de busca
        $old = cdr_feedback($form, $customer, FeedbackStatus::PENDING->value, [
            'created_at' => $now->copy()->subDays(30),
        ]);

        // Busca em período que não contém o feedback
        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDays(3),
            $now->copy()->subDays(2),
            $form->id
        );

        // Fallback deve retornar o registro mesmo fora do período original
        expect($result->pluck('id'))->toContain($old->id);
    });

    it('carrega o relacionamento customer (eager loading sem N+1)', function () {
        $form     = cdr_form();
        $customer = cdr_customer();
        $now      = Carbon::now();

        cdr_feedback($form, $customer, FeedbackStatus::PENDING->value, [
            'created_at' => $now->copy()->subHour(),
        ]);

        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDay(),
            $now->copy()->addDay(),
            $form->id
        );

        expect($result->first()->relationLoaded('customer'))->toBeTrue();
    });

    it('o fallback limita a 100 registros', function () {
        $form     = cdr_form();
        $customer = cdr_customer();
        $now      = Carbon::now();

        // Cria 105 feedbacks fora do período de busca (para acionar o fallback)
        for ($i = 0; $i < 105; $i++) {
            cdr_feedback($form, $customer, FeedbackStatus::PENDING->value, [
                'created_at' => $now->copy()->subDays(30),
            ]);
        }

        $result = cdr_repo()->getPendingFeedbacks(
            $now->copy()->subDays(3),
            $now->copy()->subDays(2),
            $form->id
        );

        expect($result)->toHaveCount(100);
    });

});

// ─── getCompletedFeedbacks ────────────────────────────────────────────────────

describe('CrmDashboardRepository — getCompletedFeedbacks', function () {

    it('retorna feedbacks com status finished', function () {
        $form     = cdr_form();
        $customer = cdr_customer();

        $finished = cdr_feedback($form, $customer, FeedbackStatus::FINISHED->value, [
            'return_at'    => null,
            'completed_at' => null,
        ]);

        $result = cdr_repo()->getCompletedFeedbacks($form->id);

        expect($result->pluck('id'))->toContain($finished->id);
    });

    it('retorna feedbacks com status legado "1"', function () {
        $form     = cdr_form();
        $customer = cdr_customer();

        $legacy = cdr_feedback($form, $customer, '1', [
            'return_at'    => null,
            'completed_at' => null,
        ]);

        $result = cdr_repo()->getCompletedFeedbacks($form->id);

        expect($result->pluck('id'))->toContain($legacy->id);
    });

    it('não retorna feedbacks de outro formulário', function () {
        $form1    = cdr_form('Form 1');
        $form2    = cdr_form('Form 2');
        $customer = cdr_customer();

        cdr_feedback($form1, $customer, FeedbackStatus::FINISHED->value, [
            'return_at'    => null,
            'completed_at' => null,
        ]);

        $result = cdr_repo()->getCompletedFeedbacks($form2->id);

        expect($result)->toBeEmpty();
    });

    it('não retorna feedbacks com status pending', function () {
        $form     = cdr_form();
        $customer = cdr_customer();

        cdr_feedback($form, $customer, FeedbackStatus::PENDING->value, [
            'return_at'    => null,
            'completed_at' => null,
        ]);

        $result = cdr_repo()->getCompletedFeedbacks($form->id);

        expect($result)->toBeEmpty();
    });

    it('carrega o relacionamento customer (eager loading sem N+1)', function () {
        $form     = cdr_form();
        $customer = cdr_customer();

        cdr_feedback($form, $customer, FeedbackStatus::FINISHED->value, [
            'return_at'    => null,
            'completed_at' => null,
        ]);

        $result = cdr_repo()->getCompletedFeedbacks($form->id);

        expect($result->first()->relationLoaded('customer'))->toBeTrue();
    });

    it('usa fallback quando não há registros com as janelas de datas', function () {
        $form     = cdr_form();
        $customer = cdr_customer();

        // Feedback completado há muito tempo (fora das janelas de 3/7 dias)
        $old = cdr_feedback($form, $customer, FeedbackStatus::FINISHED->value, [
            'return_at'    => Carbon::now()->subDays(10)->endOfDay(),
            'completed_at' => Carbon::now()->subDays(15)->endOfDay(),
        ]);

        $result = cdr_repo()->getCompletedFeedbacks($form->id);

        // Fallback deve retornar o registro
        expect($result->pluck('id'))->toContain($old->id);
    });

});
