<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;

class Notification extends Model
{
    protected $table = 'user_notifications';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'content',
        'action',
        'image',
        'status',
    ];

    /**
     * Cria uma nova notificação.
     */
    public static function store($user_id, $content, $action, $image, $status = 1): self
    {
        return self::create([
            'user_id' => $user_id,
            'content' => $content,
            'action' => $action,
            'image' => $image,
            'status' => $status,
        ]);
    }

    /**
     * Relacionamento: notificação pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Retorna a data em formato relativo simplificado.
     */
    public function getScalarDate(): string
    {
        $created = Carbon::parse($this->created_at);
        $diffInSeconds = now()->diffInSeconds($created);

        if ($diffInSeconds < 60) {
            return $diffInSeconds . ' segundo(s)';
        }

        $diffInMinutes = now()->diffInMinutes($created);
        if ($diffInMinutes < 60) {
            return $diffInMinutes . ' minuto(s)';
        }

        $diffInHours = now()->diffInHours($created);
        if ($diffInHours < 24) {
            return $diffInHours . ' hora(s)';
        }

        $diffInDays = now()->diffInDays($created);
        return $diffInDays . ' dia(s)';
    }
}
