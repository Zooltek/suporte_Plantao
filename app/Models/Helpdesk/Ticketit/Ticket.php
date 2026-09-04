<?php

namespace App\Models\Helpdesk\Ticketit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Http\Traits\ContentEllipse;
use App\Http\Traits\Purifiable;
use App\Services\Helpdesk\Ticketit\TicketAssignmentService;

class Ticket extends Model
{
    use HasFactory;
    use ContentEllipse;
    use Purifiable;

    protected $table = 'ticketit';

    /**
     * S6600: Laravel 10+ usa o array $casts em vez de $dates.
     */
    protected $casts = [
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * S1142: Múltiplos retornos simplificados para booleano.
     */
    public function hasComments(): bool
    {
        return $this->comments()->exists();
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Scopes simplificados (S1142).
     */
    public function scopeComplete(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    // --- Relacionamentos Tipados ---

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ticket_id');
    }

    /**
     * S3776: Simplificação radical do cálculo de data relativa.
     * O Laravel/Carbon já faz isso nativamente com diffForHumans().
     */
    public function getScalarDate(): string
    {
        return $this->updated_at?->diffForHumans([
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
            'options' => Carbon::JUST_NOW | Carbon::ONE_DAY_WORDS,
        ]) ?? 'N/A';
    }

    /**
     * S1142: Delega a lógica pesada para o Service especializado.
     */
    public function autoSelectAgent(): self
    {
        return (new TicketAssignmentService())->autoSelectAgentFor($this);
    }

    /**
     * S3776 & S1142: Simplificação de contagem de anexos.
     */
    public function countAttachment(): int
    {
        if (empty($this->attachments)) {
            return 0;
        }

        return count(explode(',', (string) $this->attachments));
    }
}
