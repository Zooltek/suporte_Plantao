<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Ticket\Agent;
use App\Models\Customer;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\ScheduleType;

class Schedule extends Model
{
    use HasFactory;

    protected $table = 'schedule';

    public $timestamps = true;

    /**
     * Slugs nativos preservados como aliases literais para compatibilidade
     * com código legado. O catálogo autoritativo vive em schedule_types.
     */
    public const KIND_TICKET     = 'ticket';
    public const KIND_CLIENT     = 'client';
    public const KIND_MEDICAL    = 'medical';
    public const KIND_VACATION   = 'vacation';
    public const KIND_MEETING    = 'meeting';
    public const KIND_INTERNAL   = 'internal';
    public const KIND_OTHER      = 'other';
    public const KIND_IMPLANTACAO = 'implantacao';

    protected $casts = [
        'start_at' => 'datetime',
        'requires_admin_confirmation' => 'boolean',
    ];

    /** Scopes */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'del');
    }

    /** Relacionamentos */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Ticket\Ticket::class, 'ticket_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function scheduleType(): BelongsTo
    {
        return $this->belongsTo(ScheduleType::class, 'schedule_type_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    /** Métodos auxiliares */
    public function isCompleted(): bool
    {
        return $this->status === 'com';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'fin';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'can';
    }

    public function isPending(): bool
    {
        return $this->status === 'pen';
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'pen' => 'badge-warning',
            'sch' => 'badge-primary',
            'fin' => 'badge-success',
            'can' => 'badge-secondary',
            'con' => 'badge-info',
            'del' => 'badge-danger',
            default => 'badge-primary',
        };
    }

    public function getStatusName(): string
    {
        return match ($this->status) {
            'pen' => 'PENDENTE',
            'sch' => 'AGENDADO',
            'fin' => 'FINALIZADO',
            'can' => 'CANCELADO',
            'con' => 'CONFIRMADO',
            'del' => 'EXCLUÍDO',
            default => $this->status,
        };
    }

    public function getRecordsCount(): int
    {
        return $this->records->whereNotNull('status')->count();
    }

    public function requiresAdmin(): bool
    {
        return (bool) $this->requires_admin_confirmation;
    }

    public function isTicketBased(): bool
    {
        return $this->kind === self::KIND_TICKET || !empty($this->ticket_id);
    }

    public function needsAdminConfirmation(): bool
    {
        return $this->requiresAdmin();
    }

    public function getKindLabel(): string
    {
        if ($this->relationLoaded('scheduleType') && $this->scheduleType) {
            return $this->scheduleType->label;
        }

        return ScheduleType::query()->where('slug', $this->kind)->value('label') ?? 'Agendamento';
    }

    protected function displayTitle(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (!blank($this->title)) {
                    return $this->title;
                }

                if ($this->relationLoaded('customer') && $this->customer?->trade_name) {
                    return $this->customer->trade_name;
                }

                if ($this->relationLoaded('ticket') && $this->ticket?->subject) {
                    return "Ticket #{$this->ticket->id} - {$this->ticket->subject}";
                }

                return $this->obs ?: 'Agendamento';
            }
        );
    }
}
