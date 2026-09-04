<?php

namespace App\Models\Oncall;

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OncallAttendance extends Model
{
    use HasFactory;

    protected $table = 'oncall_attendances';

    protected $fillable = [
        'uuid',
        'oncall_shift_id',
        'user_id',
        'customer_id',
        'customer_name_fallback',
        'contact_name',
        'category_id',
        'sub_category_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'adjusted_duration_minutes',
        'trouble',
        'solution',
        'admin_notes',
        'is_resolved',
        'is_approved',
        'status_id',
        'ticket_id',
        'synced_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'synced_at' => 'datetime',
        'is_resolved' => 'boolean',
        'is_approved' => 'boolean',
        'duration_minutes' => 'integer',
        'adjusted_duration_minutes' => 'integer',
    ];

    /**
     * Retorna a quantidade de minutos efetiva a considerar na folha de pagamento.
     * Se o gestor reprovou o chamado, retorna 0.
     * Se o gestor ajustou a duração, retorna a duração ajustada.
     */
    public function getEffectiveMinutesAttribute(): int
    {
        if (! $this->is_approved) {
            return 0;
        }

        return $this->adjusted_duration_minutes !== null 
            ? (int) $this->adjusted_duration_minutes 
            : (int) $this->duration_minutes;
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(OncallShift::class, 'oncall_shift_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'customer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
