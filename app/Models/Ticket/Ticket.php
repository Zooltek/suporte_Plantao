<?php

namespace App\Models\Ticket;

use App\Contracts\Helpdesk\Ticketit\SlaServiceInterface;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Department;
use App\Models\Schedule\Module as RatModule;
use App\Models\Tasks\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'ticketit';

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'rat_responses' => 'array',
        ];
    }

    protected $appends = [
        'agent_name',
        'company_name',
        'category_name',
        'sla_level',
    ];

    protected $fillable = [
        'agent_id',
        'department_id',
        'author_id',
        'origin_id',
        'company_id',
        'status_id',
        'category_id',
        'sub_category_id',
        'module_id',
        'rat_module_id',
        'task_id',
        'referenced_ticket_id',
        'completed_at',
        'contact',
        'trouble',
        'solution',
        'obs',
        'version',
        'release',
        'visible',
        'is_recurring',
        'priority_id',
        'elapsed_time',
        'elapsed_token',
        'subject',
        'content',
        'user_id',
        'rat_responses',
    ];

    public const STATUS_PENDING_ID = 4;

    public const STATUS_IN_PROGRESS_ID = 2;

    public const CATEGORY_CLIENTES_RETORNO_ID = 76;

    // RELACIONAMENTOS

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class, 'origin_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CompanyModuleType::class, 'module_id');
    }

    public function ratModule(): BelongsTo
    {
        return $this->belongsTo(RatModule::class, 'rat_module_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id', 'category_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'ticket_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ticket_id');
    }

    public function extraCategories(): HasMany
    {
        return $this->hasMany(TicketExtraCategory::class, 'ticket_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function referencedTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'referenced_ticket_id');
    }

    public function childTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'referenced_ticket_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TicketAudit::class, 'ticket_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(TicketIssue::class, 'ticket_id');
    }

    /**
     * Pré-carrega dados mínimos para cálculo de SLA sem N+1.
     */
    public function scopeWithSlaDependencies(Builder $query): Builder
    {
        return $query->with([
            'category:category_id,priority',
            'subCategory:category_id,priority',
        ]);
    }

    /**
     * Fila de pendências: ticket pendente e ainda sem responsável atribuído.
     */
    public function scopeQueuePending(Builder $query): Builder
    {
        return $query
            ->pendingStatus()
            ->withoutAssignedAgent();
    }

    /**
     * Tickets com status pendente, independentemente de responsável.
     */
    public function scopePendingStatus(Builder $query): Builder
    {
        return $query->where('status_id', self::STATUS_PENDING_ID);
    }

    /**
     * Considera "sem agente" tanto null quanto 0 por compatibilidade legada.
     */
    public function scopeWithoutAssignedAgent(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery) {
            $subQuery
                ->whereNull('agent_id')
                ->orWhere('agent_id', 0);
        });
    }

    public function scopeWithAssignedAgent(Builder $query): Builder
    {
        return $query
            ->whereNotNull('agent_id')
            ->where('agent_id', '!=', 0);
    }

    /**
     * Acesso: $ticket->agent_name
     */
    protected function agentName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->agent->name ?? '',
        );
    }

    /**
     * Acesso: $ticket->company_name
     */
    protected function companyName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->company?->trade_name ?? $this->company?->name ?? '',
        );
    }

    /**
     * Acesso: $ticket->category_name
     */
    protected function categoryName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->category_id || ! $this->sub_category_id) {
                    return '';
                }

                $catName = $this->category?->name ?? '';
                $subCatName = $this->subCategory?->name ?? '';

                return trim("{$catName} - {$subCatName}", ' -');
            }
        );
    }

    /**
     * Acesso: $ticket->calculated_priority_weight
     * Soma o peso da categoria e da subcategoria
     */
    protected function calculatedPriorityWeight(): Attribute
    {
        return Attribute::make(
            get: fn () => app(SlaServiceInterface::class)->calculatePriorityWeight($this),
        );
    }

    /**
     * Acesso: $ticket->sla_level
     * Retorna o estado semântico do tempo de espera (SLA).
     */
    protected function slaLevel(): Attribute
    {
        return Attribute::make(
            get: fn () => app(SlaServiceInterface::class)->resolveSlaLevel($this),
        );
    }

    public function getClientesRetornoCategoryId(): int
    {
        return self::CATEGORY_CLIENTES_RETORNO_ID;
    }

    public function isPending(): bool
    {
        return $this->status_id === self::STATUS_PENDING_ID;
    }

    public function hasAssignedAgent(): bool
    {
        if ($this->agent_id === null) {
            return false;
        }

        return (int) $this->agent_id !== 0;
    }

    public function isQueuePending(): bool
    {
        return $this->isPending() && ! $this->hasAssignedAgent();
    }
}
