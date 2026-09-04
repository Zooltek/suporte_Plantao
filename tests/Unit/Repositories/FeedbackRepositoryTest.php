<?php

/**
 * Testes de integração do FeedbackRepository — SQLite in-memory.
 *
 * Cobrem todos os métodos públicos da implementação, validando que as queries
 * produzem os resultados esperados sem acionar lógica de negócio do Service.
 */

use App\Enums\FeedbackStatus;
use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Element;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Crm\Feedback\Form;
use App\Models\Customer;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\UserRating;
use App\Repositories\FeedbackRepository;
use Carbon\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function fr_form(): Form
{
    return Form::factory()->create();
}

function fr_customer(): Customer
{
    return Customer::factory()->create();
}

function fr_feedback(Form $form, Customer $customer, string $status = 'fin'): Feedback
{
    return Feedback::factory()->create([
        'form_id'     => $form->id,
        'customer_id' => $customer->id,
        'status'      => $status,
    ]);
}

// ─── findFeedback ─────────────────────────────────────────────────────────────

describe('FeedbackRepository — findFeedback', function () {

    it('retorna o Feedback pelo id', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $feedback = fr_feedback($form, $customer);

        $found = (new FeedbackRepository())->findFeedback($feedback->id);

        expect($found->id)->toBe($feedback->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => (new FeedbackRepository())->findFeedback(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── findFeedbackWithRelations ────────────────────────────────────────────────

describe('FeedbackRepository — findFeedbackWithRelations', function () {

    it('carrega relacionamentos customer e elements', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $feedback = fr_feedback($form, $customer);

        // Cria um elemento para confirmar que a relação é carregada
        Element::create([
            'feedback_id' => $feedback->id,
            'element_id'  => ElementType::factory()->create(['form_id' => $form->id])->id,
            'value'       => 'teste',
        ]);

        $found = (new FeedbackRepository())->findFeedbackWithRelations($feedback->id);

        expect($found->relationLoaded('customer'))->toBeTrue()
            ->and($found->relationLoaded('elements'))->toBeTrue()
            ->and($found->elements)->not->toBeEmpty();
    });

});

// ─── saveFeedback ─────────────────────────────────────────────────────────────

describe('FeedbackRepository — saveFeedback', function () {

    it('persiste um novo Feedback no banco', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $user     = User::factory()->create();

        $feedback              = new Feedback();
        $feedback->form_id     = $form->id;
        $feedback->customer_id = $customer->id;
        $feedback->user_id     = $user->id;
        $feedback->status      = FeedbackStatus::FINISHED->value;
        $feedback->completed_at = Carbon::now();

        (new FeedbackRepository())->saveFeedback($feedback);

        expect($feedback->id)->not->toBeNull();
        $this->assertDatabaseHas('crm_feedback', ['id' => $feedback->id]);
    });

});

// ─── cancelFeedbackWithRatings ────────────────────────────────────────────────

describe('FeedbackRepository — cancelFeedbackWithRatings', function () {

    it('cancela o feedback e apaga os UserRatings vinculados', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $user     = User::factory()->create();
        $feedback = fr_feedback($form, $customer, FeedbackStatus::PENDING->value);

        UserRating::create([
            'user_id'     => $user->id,
            'feedback_id' => $feedback->id,
            'rating'      => 5,
        ]);

        (new FeedbackRepository())->cancelFeedbackWithRatings($feedback->id);

        $this->assertDatabaseHas('crm_feedback', [
            'id'     => $feedback->id,
            'status' => FeedbackStatus::CANCELED->value,
        ]);
        $this->assertDatabaseMissing('user_ratings', [
            'feedback_id' => $feedback->id,
        ]);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => (new FeedbackRepository())->cancelFeedbackWithRatings(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── cancelPendingFeedbacksForCustomer ───────────────────────────────────────

describe('FeedbackRepository — cancelPendingFeedbacksForCustomer', function () {

    it('cancela todos os feedbacks pendentes do cliente', function () {
        $form     = fr_form();
        $customer = fr_customer();

        $pending1 = fr_feedback($form, $customer, FeedbackStatus::PENDING->value);
        $pending2 = fr_feedback($form, $customer, 'open');
        $finished = fr_feedback($form, $customer, FeedbackStatus::FINISHED->value);

        (new FeedbackRepository())->cancelPendingFeedbacksForCustomer($customer->id);

        $this->assertDatabaseHas('crm_feedback', [
            'id'     => $pending1->id,
            'status' => FeedbackStatus::CANCELED->value,
        ]);
        $this->assertDatabaseHas('crm_feedback', [
            'id'     => $pending2->id,
            'status' => FeedbackStatus::CANCELED->value,
        ]);
        // Feedback finalizado não deve ter sido alterado
        $this->assertDatabaseHas('crm_feedback', [
            'id'     => $finished->id,
            'status' => FeedbackStatus::FINISHED->value,
        ]);
    });

});

// ─── findForm ─────────────────────────────────────────────────────────────────

describe('FeedbackRepository — findForm', function () {

    it('retorna o Form pelo id', function () {
        $form = fr_form();

        $found = (new FeedbackRepository())->findForm($form->id);

        expect($found->id)->toBe($form->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => (new FeedbackRepository())->findForm(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── firstForm ────────────────────────────────────────────────────────────────

describe('FeedbackRepository — firstForm', function () {

    it('retorna o primeiro Form cadastrado', function () {
        $form = fr_form();

        $found = (new FeedbackRepository())->firstForm();

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($form->id);
    });

    it('retorna null quando não há Forms', function () {
        // Sem criar nenhum form
        expect((new FeedbackRepository())->firstForm())->toBeNull();
    });

});

// ─── resolveForm ──────────────────────────────────────────────────────────────

describe('FeedbackRepository — resolveForm', function () {

    it('retorna o Form pelo id quando existe', function () {
        $form = fr_form();

        expect((new FeedbackRepository())->resolveForm($form->id)->id)->toBe($form->id);
    });

    it('retorna o primeiro Form quando id não encontrado', function () {
        $form = fr_form();

        $found = (new FeedbackRepository())->resolveForm(999999);

        expect($found->id)->toBe($form->id);
    });

    it('retorna o primeiro Form quando id é null', function () {
        $form = fr_form();

        $found = (new FeedbackRepository())->resolveForm(null);

        expect($found->id)->toBe($form->id);
    });

    it('retorna null quando não há Forms e id é null', function () {
        expect((new FeedbackRepository())->resolveForm(null))->toBeNull();
    });

});

// ─── findCustomer ─────────────────────────────────────────────────────────────

describe('FeedbackRepository — findCustomer', function () {

    it('retorna o Customer pelo id', function () {
        $customer = fr_customer();

        $found = (new FeedbackRepository())->findCustomer($customer->id);

        expect($found->id)->toBe($customer->id);
    });

    it('retorna null para id inexistente', function () {
        expect((new FeedbackRepository())->findCustomer(999999))->toBeNull();
    });

});

// ─── getAllCustomers ──────────────────────────────────────────────────────────

describe('FeedbackRepository — getAllCustomers', function () {

    it('retorna todos os customers ordenados por trade_name', function () {
        Customer::factory()->create(['trade_name' => 'Zebra Corp']);
        Customer::factory()->create(['trade_name' => 'Alpha Corp']);

        $customers = (new FeedbackRepository())->getAllCustomers();

        expect($customers->first()->trade_name)->toBe('Alpha Corp')
            ->and($customers->last()->trade_name)->toBe('Zebra Corp');
    });

});

// ─── getElementTypes ──────────────────────────────────────────────────────────

describe('FeedbackRepository — getElementTypes', function () {

    it('retorna os ElementTypes de um Form', function () {
        $form = fr_form();
        ElementType::factory()->create(['form_id' => $form->id]);
        ElementType::factory()->create(['form_id' => $form->id]);

        $types = (new FeedbackRepository())->getElementTypes($form->id);

        expect($types)->toHaveCount(2);
    });

    it('não retorna ElementTypes de outro Form', function () {
        $form1 = fr_form();
        $form2 = fr_form();
        ElementType::factory()->create(['form_id' => $form1->id]);

        $types = (new FeedbackRepository())->getElementTypes($form2->id);

        expect($types)->toBeEmpty();
    });

});

// ─── getElementTypesForView ───────────────────────────────────────────────────

describe('FeedbackRepository — getElementTypesForView', function () {

    it('retorna ElementTypes ordenados por sort_order', function () {
        $form = fr_form();
        ElementType::factory()->create(['form_id' => $form->id, 'sort_order' => 3]);
        ElementType::factory()->create(['form_id' => $form->id, 'sort_order' => 1]);
        ElementType::factory()->create(['form_id' => $form->id, 'sort_order' => 2]);

        $types = (new FeedbackRepository())->getElementTypesForView($form->id);

        expect($types->pluck('sort_order')->all())->toBe([1, 2, 3]);
    });

});

// ─── saveElement ──────────────────────────────────────────────────────────────

describe('FeedbackRepository — saveElement', function () {

    it('cria um Element quando não existe', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $feedback = fr_feedback($form, $customer);
        $type     = ElementType::factory()->create(['form_id' => $form->id]);

        (new FeedbackRepository())->saveElement([
            'feedback_id' => $feedback->id,
            'element_id'  => $type->id,
            'value'       => 'resposta',
        ]);

        $this->assertDatabaseHas('crm_feedback_element', [
            'feedback_id' => $feedback->id,
            'element_id'  => $type->id,
            'value'       => 'resposta',
        ]);
    });

    it('atualiza o value quando Element já existe', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $feedback = fr_feedback($form, $customer);
        $type     = ElementType::factory()->create(['form_id' => $form->id]);

        Element::create([
            'feedback_id' => $feedback->id,
            'element_id'  => $type->id,
            'value'       => 'antigo',
        ]);

        (new FeedbackRepository())->saveElement([
            'feedback_id' => $feedback->id,
            'element_id'  => $type->id,
            'value'       => 'novo',
        ]);

        $this->assertDatabaseHas('crm_feedback_element', [
            'feedback_id' => $feedback->id,
            'element_id'  => $type->id,
            'value'       => 'novo',
        ]);
    });

});

// ─── saveUserRating ───────────────────────────────────────────────────────────

describe('FeedbackRepository — saveUserRating', function () {

    it('cria um UserRating quando não existe', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $user     = User::factory()->create();
        $feedback = fr_feedback($form, $customer);

        (new FeedbackRepository())->saveUserRating([
            'user_id'     => $user->id,
            'feedback_id' => $feedback->id,
            'rating'      => 10,
        ]);

        $this->assertDatabaseHas('user_ratings', [
            'user_id'     => $user->id,
            'feedback_id' => $feedback->id,
            'rating'      => 10,
        ]);
    });

    it('atualiza o rating quando UserRating já existe', function () {
        $form     = fr_form();
        $customer = fr_customer();
        $user     = User::factory()->create();
        $feedback = fr_feedback($form, $customer);

        UserRating::create([
            'user_id'     => $user->id,
            'feedback_id' => $feedback->id,
            'rating'      => 5,
        ]);

        (new FeedbackRepository())->saveUserRating([
            'user_id'     => $user->id,
            'feedback_id' => $feedback->id,
            'rating'      => 0,
        ]);

        $this->assertDatabaseHas('user_ratings', [
            'user_id'     => $user->id,
            'feedback_id' => $feedback->id,
            'rating'      => 0,
        ]);
    });

});

// ─── getCandidateCustomerIds ──────────────────────────────────────────────────

describe('FeedbackRepository — getCandidateCustomerIds', function () {

    it('retorna company_ids com tickets completados no período ordenados por quantidade', function () {
        $user = User::factory()->create();
        $cat  = \App\Models\Category::factory()->create(['parent_id' => 0]);

        $companyA = \App\Models\Company::factory()->create();
        $companyB = \App\Models\Company::factory()->create();

        $completedAt = Carbon::now()->subHour();

        // Empresa B tem 2 tickets, Empresa A tem 1 — B deve vir primeiro
        Ticket::factory()->create([
            'company_id'   => $companyB->id,
            'completed_at' => $completedAt,
            'category_id'  => $cat->category_id,
        ]);
        Ticket::factory()->create([
            'company_id'   => $companyB->id,
            'completed_at' => $completedAt,
            'category_id'  => $cat->category_id,
        ]);
        Ticket::factory()->create([
            'company_id'   => $companyA->id,
            'completed_at' => $completedAt,
            'category_id'  => $cat->category_id,
        ]);

        $start = Carbon::now()->subDay();
        $end   = Carbon::now()->addDay();

        $ids = (new FeedbackRepository())->getCandidateCustomerIds($start, $end);

        expect($ids->first())->toBe($companyB->id)
            ->and($ids)->toContain($companyA->id);
    });

    it('retorna coleção vazia quando não há tickets no período', function () {
        $start = Carbon::now()->subDay();
        $end   = Carbon::now()->subHour();

        $ids = (new FeedbackRepository())->getCandidateCustomerIds($start, $end);

        expect($ids)->toBeEmpty();
    });

});

// ─── getBlockedCustomerIds ────────────────────────────────────────────────────

describe('FeedbackRepository — getBlockedCustomerIds', function () {

    it('retorna customer_ids com feedback pendente para o form', function () {
        $form      = fr_form();
        $customer  = fr_customer();
        $customer2 = fr_customer();

        fr_feedback($form, $customer, FeedbackStatus::PENDING->value);
        fr_feedback($form, $customer2, FeedbackStatus::FINISHED->value); // não deve ser bloqueado

        $candidateIds = collect([$customer->id, $customer2->id]);
        $blocked      = (new FeedbackRepository())->getBlockedCustomerIds($form->id, $candidateIds);

        expect($blocked)->toContain($customer->id)
            ->and($blocked)->not->toContain($customer2->id);
    });

});

// ─── getRecentlyProcessedCustomerIds ─────────────────────────────────────────

describe('FeedbackRepository — getRecentlyProcessedCustomerIds', function () {

    it('retorna customer_ids com feedback finalizado/cancelado no período', function () {
        $form     = fr_form();
        $customer = fr_customer();

        // Feedback recente finalizado
        Feedback::factory()->create([
            'form_id'     => $form->id,
            'customer_id' => $customer->id,
            'status'      => FeedbackStatus::FINISHED->value,
            'created_at'  => Carbon::now()->subHour(),
        ]);

        $candidateIds = collect([$customer->id]);
        $result       = (new FeedbackRepository())->getRecentlyProcessedCustomerIds(
            $form->id,
            Carbon::now()->subDay(),
            $candidateIds
        );

        expect($result)->toContain($customer->id);
    });

});

// ─── getRecentFeedbacksForCustomer ────────────────────────────────────────────

describe('FeedbackRepository — getRecentFeedbacksForCustomer', function () {

    it('retorna feedbacks recentes limitados pelo parâmetro limit', function () {
        $form     = fr_form();
        $customer = fr_customer();

        for ($i = 0; $i < 8; $i++) {
            fr_feedback($form, $customer);
        }

        $result = (new FeedbackRepository())->getRecentFeedbacksForCustomer($customer->id, $form->id, 6);

        expect($result)->toHaveCount(6);
    });

    it('não retorna feedbacks de outro cliente', function () {
        $form      = fr_form();
        $customer1 = fr_customer();
        $customer2 = fr_customer();

        fr_feedback($form, $customer1);

        $result = (new FeedbackRepository())->getRecentFeedbacksForCustomer($customer2->id, $form->id, 6);

        expect($result)->toBeEmpty();
    });

});

// ─── getFeedbackStatsByForm ───────────────────────────────────────────────────

describe('FeedbackRepository — getFeedbackStatsByForm', function () {

    it('agrupa estatísticas por customer_id', function () {
        $form     = fr_form();
        $customer = fr_customer();

        fr_feedback($form, $customer, FeedbackStatus::FINISHED->value);
        fr_feedback($form, $customer, FeedbackStatus::FINISHED->value);
        fr_feedback($form, $customer, FeedbackStatus::CANCELED->value);

        $stats = (new FeedbackRepository())->getFeedbackStatsByForm($form->id);

        expect($stats->has($customer->id))->toBeTrue();
        $customerStats = $stats->get($customer->id);
        expect($customerStats->sum('count'))->toBe(3);
    });

});

// ─── getTicketCountsByPeriod ──────────────────────────────────────────────────

describe('FeedbackRepository — getTicketCountsByPeriod', function () {

    it('conta tickets por company_id no período', function () {
        $cat     = \App\Models\Category::factory()->create(['parent_id' => 0]);
        $company = \App\Models\Company::factory()->create();
        $now     = Carbon::now();

        Ticket::factory()->create([
            'company_id'   => $company->id,
            'completed_at' => $now->copy()->subHour(),
            'category_id'  => $cat->category_id,
        ]);
        Ticket::factory()->create([
            'company_id'   => $company->id,
            'completed_at' => $now->copy()->subHour(),
            'category_id'  => $cat->category_id,
        ]);

        $counts = (new FeedbackRepository())->getTicketCountsByPeriod(
            $now->copy()->subDay(),
            $now->copy()->addDay()
        );

        expect((int) $counts->get($company->id))->toBe(2);
    });

});
