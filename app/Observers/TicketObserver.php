<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketAudit;
use Illuminate\Support\Facades\Auth;

class TicketObserver
{
    /**
     * Campos monitorados para auditoria automática de alterações.
     * Cada campo pode ter um resolver opcional para exibir o nome em vez do ID.
     */
    private const WATCHED_FIELDS = [
        'status_id',
        'agent_id',
        'department_id',
        'company_id',
        'category_id',
        'sub_category_id',
        'priority_id',
        'contact',
        'trouble',
        'solution',
        'obs',
        'is_recurring',
        'referenced_ticket_id',
    ];

    public function created(Ticket $ticket): void
    {
        $this->record($ticket, 'created', 'Chamado criado');
    }

    public function updated(Ticket $ticket): void
    {
        $dirty = $ticket->getDirty();
        $userId = Auth::guard('admin')->id() ?? Auth::guard('web')->id() ?? Auth::id() ?? $ticket->user_id ?? $ticket->agent_id;

        if ($ticket->wasChanged('status_id') || $ticket->wasChanged('completed_at')) {
            if ($ticket->completed_at !== null || Status::isTerminal((int) $ticket->status_id)) {
                try {
                    app(\App\Services\WhatsApp\WhatsAppBotReleaseService::class)->releaseTicketConversations($ticket, 'ticket_closed');
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[WhatsApp] Falha ao acionar releaseTicketConversations no observer: '.$e->getMessage());
                }
            }
        }

        foreach (self::WATCHED_FIELDS as $field) {
            if (! array_key_exists($field, $dirty)) {
                continue;
            }

            $oldRaw = $ticket->getOriginal($field);
            $newRaw = $ticket->getAttribute($field);

            if ($oldRaw === $newRaw) {
                continue;
            }

            $event = $this->resolveEvent($field);

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
                'event' => $event,
                'operation' => $this->buildDescription($field, $oldRaw, $newRaw),
                'field' => $field,
                'old_value' => $this->resolveLabel($field, $oldRaw),
                'new_value' => $this->resolveLabel($field, $newRaw),
            ]);
        }
    }

    private function record(Ticket $ticket, string $event, string $description): void
    {
        $userId = Auth::guard('admin')->id() ?? Auth::guard('web')->id() ?? Auth::id() ?? $ticket->user_id;

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'event' => $event,
            'operation' => $description,
            'field' => null,
            'old_value' => null,
            'new_value' => null,
        ]);
    }

    private function resolveEvent(string $field): string
    {
        return match ($field) {
            'status_id' => 'status_changed',
            'agent_id' => 'agent_changed',
            'department_id' => 'department_changed',
            default => 'field_changed',
        };
    }

    private function buildDescription(string $field, mixed $old, mixed $new): string
    {
        $label = TicketAudit::FIELD_LABELS[$field] ?? $field;
        $oldLabel = $this->resolveLabel($field, $old);
        $newLabel = $this->resolveLabel($field, $new);

        return "Campo '{$label}' alterado de '{$oldLabel}' para '{$newLabel}'";
    }

    private function resolveLabel(string $field, mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        return match ($field) {
            'status_id' => Status::find($value)?->name ?? (string) $value,
            'agent_id' => Agent::find($value)?->name ?? (string) $value,
            'department_id' => Department::find($value)?->name ?? (string) $value,
            'company_id' => Company::find($value)?->trade_name ?? Company::find($value)?->name ?? (string) $value,
            'category_id',
            'sub_category_id' => Category::where('category_id', $value)->first()?->name ?? (string) $value,
            'is_recurring' => $value ? 'Sim' : 'Não',
            default => (string) $value,
        };
    }
}
