<?php

namespace App\Models\Ticket;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAudit extends Model
{
    protected $table = 'ticketit_audits';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'event',
        'operation',
        'field',
        'old_value',
        'new_value',
    ];

    // Mapeamento de campos técnicos para nomes legíveis
    public const FIELD_LABELS = [
        'status_id' => 'Status',
        'agent_id' => 'Agente',
        'department_id' => 'Departamento',
        'company_id' => 'Empresa',
        'category_id' => 'Categoria',
        'sub_category_id' => 'Sub-categoria',
        'priority_id' => 'Prioridade',
        'contact' => 'Contato',
        'trouble' => 'Problema',
        'solution' => 'Solução',
        'obs' => 'Observação',
        'is_recurring' => 'Recorrente',
        'referenced_ticket_id' => 'Chamado Referente',
    ];

    public const EVENT_LABELS = [
        'created' => 'Criado',
        'updated' => 'Atualizado',
        'field_changed' => 'Campo alterado',
        'status_changed' => 'Status alterado',
        'agent_changed' => 'Agente alterado',
        'department_changed' => 'Departamento alterado',
        'department_backfill' => 'Departamento reclassificado (backfill)',
        'closed' => 'Encerrado',
        'captured' => 'Capturado',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFieldLabelAttribute(): string
    {
        return self::FIELD_LABELS[$this->field] ?? $this->field ?? '—';
    }

    public function getEventLabelAttribute(): string
    {
        return self::EVENT_LABELS[$this->event] ?? $this->event;
    }
}
