<?php

namespace App\Models\Helpdesk\Chat;

use App\Models\User;
use Database\Factories\Helpdesk\Chat\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
    ];

    /**
     * Indica se o model deve ter timestamps (created_at, updated_at).
     * Essencial para mensagens para sabermos quando foram enviadas.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Relacionamento: O autor da mensagem (usuário).
     */
    public function owner(): BelongsTo
    {
        // Assume-se que o User está em App\Models\User
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: A conversa à qual esta mensagem pertence.
     * (Adicionado para completar a lógica do sistema de chat)
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function newFactory(): MessageFactory
    {
        return MessageFactory::new();
    }
}
