<?php

namespace App\Models\Oncall;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OncallShift extends Model
{
    use HasFactory;

    protected $table = 'oncall_shifts';

    protected $fillable = [
        'uuid',
        'user_id',
        'started_at',
        'ended_at',
        'total_standby_minutes',
        'total_worked_minutes',
        'status',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_standby_minutes' => 'integer',
        'total_worked_minutes' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(OncallAttendance::class, 'oncall_shift_id');
    }

    /**
     * Recalcula os minutos de sobreaviso e atendimento do turno.
     */
    public function recalculateHours(): void
    {
        $workedMinutes = (int) $this->attendances()->get()->sum(fn($a) => $a->effective_minutes);
        $this->total_worked_minutes = $workedMinutes;

        if ($this->started_at && $this->ended_at) {
            $totalShiftMinutes = max(0, $this->started_at->diffInMinutes($this->ended_at));
            // Sobreaviso líquido = tempo total do plantão menos tempo em que estava efetivamente atendendo
            $this->total_standby_minutes = max(0, $totalShiftMinutes - $workedMinutes);
        }

        $this->save();
    }
}
