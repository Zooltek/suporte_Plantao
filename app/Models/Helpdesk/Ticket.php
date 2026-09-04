<?php

namespace App\Models\Helpdesk;

use App\Models\Ticket\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    /**
     * Define a tabela associada.
     */
    protected $table = 'ticketit';

    protected $fillable = [
        'subject',
        'content',
        'html',
        'user_id',
        'agent_id',
        'status_id',
        'priority_id',
        'category_id',
        'company_id',
        'origin_id',
        'completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    // Relacionamento previsto para o fluxo "Ticket -> Attendance"
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
