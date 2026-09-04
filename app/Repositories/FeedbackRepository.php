<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\FeedbackRepositoryInterface;
use App\Enums\FeedbackStatus;
use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Element;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Crm\Feedback\Form;
use App\Models\Customer;
use App\Models\Ticket\Ticket;
use App\Models\UserRating;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class FeedbackRepository implements FeedbackRepositoryInterface
{
    // -------------------------------------------------------------------------
    // Feedback
    // -------------------------------------------------------------------------

    public function findFeedback(int $id): Feedback
    {
        return Feedback::findOrFail($id);
    }

    public function findFeedbackWithRelations(int $id): Feedback
    {
        return Feedback::with(['customer', 'elements'])->findOrFail($id);
    }

    public function saveFeedback(Feedback $feedback): void
    {
        $feedback->save();
    }

    public function cancelFeedbackWithRatings(int $feedbackId): void
    {
        DB::transaction(static function () use ($feedbackId): void {
            $feedback = Feedback::findOrFail($feedbackId);
            $feedback->update(['status' => FeedbackStatus::CANCELED->value]);

            UserRating::where('feedback_id', $feedbackId)->delete();
        });
    }

    public function cancelPendingFeedbacksForCustomer(int $customerId): void
    {
        Feedback::where('customer_id', $customerId)
            ->whereIn('status', [
                FeedbackStatus::PENDING->value,
                'open',
            ])
            ->update(['status' => FeedbackStatus::CANCELED->value]);
    }

    public function getRecentFeedbacksForCustomer(int $customerId, int $formId, int $limit = 6): Collection
    {
        return Feedback::query()
            ->where('customer_id', $customerId)
            ->where('form_id', $formId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'status', 'contact', 'created_at', 'completed_at', 'return_at', 'return_content']);
    }

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public function findForm(int $id): Form
    {
        return Form::findOrFail($id);
    }

    public function firstForm(): ?Form
    {
        return Form::query()->first();
    }

    public function resolveForm(?int $formId): ?Form
    {
        if ($formId) {
            return Form::query()->find($formId) ?? Form::query()->first();
        }

        return Form::query()->first();
    }

    // -------------------------------------------------------------------------
    // Customer
    // -------------------------------------------------------------------------

    public function findCustomer(int $customerId): ?Customer
    {
        return Customer::query()->find($customerId);
    }

    public function getAllCustomers(): Collection
    {
        return Customer::query()
            ->orderBy('trade_name')
            ->get(['id', 'trade_name', 'contact_name', 'contact_email', 'email', 'phone', 'telephone_2']);
    }

    // -------------------------------------------------------------------------
    // ElementType / Element / UserRating
    // -------------------------------------------------------------------------

    public function getElementTypes(int $formId): Collection
    {
        return ElementType::where('form_id', $formId)->get();
    }

    public function getElementTypesForView(int $formId): Collection
    {
        return ElementType::query()
            ->where('form_id', $formId)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'label', 'type', 'data', 'sort_order']);
    }

    public function saveElement(array $data): Element
    {
        return Element::query()->updateOrCreate(
            [
                'feedback_id' => $data['feedback_id'],
                'element_id'  => $data['element_id'],
            ],
            $data
        );
    }

    public function saveUserRating(array $data): UserRating
    {
        return UserRating::query()->updateOrCreate(
            [
                'user_id'     => $data['user_id'],
                'feedback_id' => $data['feedback_id'],
            ],
            ['rating' => $data['rating']]
        );
    }

    // -------------------------------------------------------------------------
    // Ticket queries (para seleção de candidatos)
    // -------------------------------------------------------------------------

    public function getCandidateCustomerIds(Carbon $start, Carbon $end): SupportCollection
    {
        return Ticket::query()
            ->select('company_id')
            ->selectRaw('COUNT(*) AS tickets_count')
            ->whereNotNull('company_id')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('company_id')
            ->orderByDesc('tickets_count')
            ->pluck('company_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function getBlockedCustomerIds(int $formId, SupportCollection $candidateIds): array
    {
        return Feedback::query()
            ->where('form_id', $formId)
            ->whereIn('status', [
                FeedbackStatus::PENDING->value,
                'open',
            ])
            ->whereIn('customer_id', $candidateIds)
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function getRecentlyProcessedCustomerIds(
        int $formId,
        Carbon $start,
        SupportCollection $candidateIds
    ): array {
        return Feedback::query()
            ->where('form_id', $formId)
            ->whereIn('status', [
                FeedbackStatus::FINISHED->value,
                FeedbackStatus::CANCELED->value,
                '1',
            ])
            ->whereBetween('created_at', [$start->copy()->startOfDay(), now()->endOfDay()])
            ->whereIn('customer_id', $candidateIds)
            ->pluck('customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function getTicketsWithAgentByCompany(int $companyId, Carbon $start, Carbon $end): Collection
    {
        return Ticket::with('agent')
            ->whereBetween('completed_at', [$start, $end])
            ->where('company_id', $companyId)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Estatísticas para view de criação
    // -------------------------------------------------------------------------

    public function getFeedbackStatsByForm(int $formId): \Illuminate\Support\Collection
    {
        return Feedback::query()
            ->selectRaw('customer_id, status, count(*) as count')
            ->where('form_id', $formId)
            ->groupBy('customer_id', 'status')
            ->get()
            ->groupBy('customer_id');
    }

    public function getTicketCountsByPeriod(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        return Ticket::query()
            ->selectRaw('company_id, count(*) as count')
            ->whereBetween('completed_at', [$start->copy()->startOfDay(), Carbon::now()->endOfDay()])
            ->groupBy('company_id')
            ->pluck('count', 'company_id');
    }

    // =========================================================================
    // Admin CRM — gerenciamento de formulários e elementos
    // =========================================================================

    public function getAllForms(): Collection
    {
        return Form::orderBy('name')->get();
    }

    public function createForm(array $data): Form
    {
        return Form::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updateForm(Form $form, array $data): Form
    {
        $form->update([
            'name'        => $data['name']        ?? $form->name,
            'description' => $data['description'] ?? $form->description,
        ]);

        return $form;
    }

    public function createFormElement(array $data): ElementType
    {
        return ElementType::create([
            'form_id'    => $data['form_id']    ?? null,
            'label'      => $data['label'],
            'name'       => $data['name'],
            'type'       => $data['type'],
            'data'       => $data['data']       ?? null,
            'sort_order' => $data['sort_order'] ?? 1,
        ]);
    }

    public function updateFormElement(ElementType $element, array $data): ElementType
    {
        $element->update([
            'label'      => $data['label']      ?? $element->label,
            'name'       => $data['name']       ?? $element->name,
            'type'       => $data['type']       ?? $element->type,
            'data'       => $data['data']       ?? $element->data,
            'sort_order' => $data['sort_order'] ?? $element->sort_order,
        ]);

        return $element;
    }

    public function deleteFormElement(ElementType $element): bool
    {
        return (bool) $element->delete();
    }

    public function getFeedbackFormStats(): array
    {
        return [
            'total_forms'    => Form::count(),
            'active_forms'   => Form::count(),
            'total_elements' => ElementType::count(),
        ];
    }

    public function getFormElements(?int $formId = null): Collection
    {
        $query = ElementType::orderBy('sort_order');

        if ($formId) {
            $query->where('form_id', $formId);
        }

        return $query->get();
    }
}
