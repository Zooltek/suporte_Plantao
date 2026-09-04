<?php

namespace App\Models\Ticket;

use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Status extends Model
{
    use HasFactory;

    protected $table = 'ticketit_statuses';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'color',
        'status',
        'is_terminal',
        'requires_schedule',
        'requires_solution',
        'requires_agent',
    ];

    protected $casts = [
        'is_terminal' => 'boolean',
        'requires_schedule' => 'boolean',
        'requires_solution' => 'boolean',
        'requires_agent' => 'boolean',
    ];

    /**
     * Verifica se um status é terminal (chamado finalizado/encerrado).
     * Evita hardcoding de IDs no domínio — a decisão fica no banco de dados.
     */
    public static function isTerminal(int $statusId): bool
    {
        return (bool) static::where('id', $statusId)->value('is_terminal');
    }

    /**
     * Verifica se um status exige agendamento após salvar o chamado.
     */
    public static function requiresSchedule(int $statusId): bool
    {
        return (bool) static::where('id', $statusId)->value('requires_schedule');
    }

    /**
     * Verifica se um status exige o preenchimento do campo "Solução".
     * Apenas chamados Resolvidos possuem uma solução a documentar.
     */
    public static function requiresSolution(int $statusId): bool
    {
        return (bool) static::where('id', $statusId)->value('requires_solution');
    }

    /**
     * Escopo para filtrar apenas statuses que encerram um chamado.
     */
    public function scopeTerminal($query)
    {
        return $query->where('is_terminal', true);
    }

    /**
     * Escopo para filtrar apenas statuses que exigem solução.
     */
    public function scopeRequiresSolution($query)
    {
        return $query->where('requires_solution', true);
    }

    /**
     * Verifica se um status exige atribuição de agente.
     * Substitui o hardcode in_array([2, 3, 5]) em SaveTicketRequest.
     */
    public static function requiresAgent(int $statusId): bool
    {
        return (bool) static::where('id', $statusId)->value('requires_agent');
    }

    /**
     * Escopo para filtrar apenas statuses que exigem agente atribuído.
     */
    public function scopeRequiresAgent($query)
    {
        return $query->where('requires_agent', true);
    }

    /**
     * Lista enxuta para selects/filtros do usuário final, ocultando duplicatas
     * por nome enquanto o catálogo administrativo permanece íntegro.
     */
    public static function orderedDistinctForSelection(): Collection
    {
        return static::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->unique(fn (self $status) => mb_strtolower(trim((string) $status->name)))
            ->values();
    }

    public static function manualDecisionOptions(): Collection
    {
        return static::orderedByIdSequence(TicketStatusCatalog::manualDecisionIds());
    }

    public static function quickUpdateOptions(): Collection
    {
        return static::orderedByIdSequence(TicketStatusCatalog::quickUpdateIds());
    }

    public static function closeWorkflowOptions(): Collection
    {
        return static::orderedByIdSequence(TicketStatusCatalog::closeWorkflowIds());
    }

    public static function isRequestStatus(int $statusId): bool
    {
        return TicketStatusCatalog::isRequestStatus($statusId);
    }

    protected static function newFactory()
    {
        return \Database\Factories\Ticket\StatusFactory::new();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @param  int[]  $ids
     */
    private static function orderedByIdSequence(array $ids): Collection
    {
        $statuses = static::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(static fn (int $id): ?self => $statuses->get($id))
            ->filter()
            ->values();
    }
}
