<?php

namespace App\Models\Schedule;

use App\Models\Tasks\Task;
use Database\Factories\Schedule\RecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Ticket\Agent;

class Record extends Model
{
    use HasFactory;

    protected $table = 'schedule_record';

    protected static function newFactory(): RecordFactory
    {
        return RecordFactory::new();
    }

    public $timestamps = true;

    protected $fillable = [
        'module_id',
        'customer_id',
        'agent_id',
        'status',
        'start',
        'end',
        'interval_start',
        'interval_end',
        'resolved',
    ];

    protected $casts = [
        'start'          => 'datetime',
        'end'            => 'datetime',
        'interval_start' => 'datetime',
        'interval_end'   => 'datetime',
        'resolved'       => 'boolean',
    ];

    /** Relacionamentos */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(Element::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'schedule_record_task', 'record_id', 'task_id')
            ->withTimestamps();
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /** Scopes */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Minutos líquidos do atendimento: (fim - início) - intervalo.
     */
    public function getTotalMinutesAttribute(): int
    {
        if (!$this->start || !$this->end) {
            return 0;
        }

        $minutes = (int) $this->start->diffInMinutes($this->end);

        if ($this->interval_start && $this->interval_end) {
            $minutes -= (int) $this->interval_start->diffInMinutes($this->interval_end);
        }

        return max(0, $minutes);
    }
}
