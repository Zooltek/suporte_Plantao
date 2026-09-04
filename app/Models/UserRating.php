<?php

namespace App\Models;

use App\Models\Crm\Feedback;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRating extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_ratings';
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atributos que podem ser preenchidos em massa.
     * Necessário para o método UserRating::create() usado no seu Controller.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'feedback_id',
        'rating',
    ];

    /**
     * * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id'     => 'integer',
            'feedback_id' => 'integer',
            'rating'      => 'integer',
        ];
    }

    /**
     * Relacionamento com o Agente/Usuário que recebeu a nota.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com o Feedback de origem.
     */
    public function feedback(): BelongsTo
    {
        return $this->belongsTo(Feedback::class);
    }
}
