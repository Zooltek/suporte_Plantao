<?php

namespace App\Models\Helpdesk\Ticketit;

use Database\Factories\Helpdesk\Ticketit\CategoryFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    /** @var string */
    protected $table = 'ticketit_categories';

    /** @var array<int, string> */
    protected $fillable = ['name', 'color'];

    /**
     * Indica que esta model não utiliza timestamps (created_at/updated_at).
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relacionamento com Tickets.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    /**
     * Relacionamento com Agentes através da tabela pivot.
     * S6600: Removido barras invertidas desnecessárias no namespace.
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(
            Agent::class,
            'ticketit_categories_users',
            'category_id',
            'user_id'
        );
    }
    protected static function newFactory()
    {
        return CategoryFactory::new();
    }
}
