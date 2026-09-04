<?php

namespace App\Models\Helpdesk\Ticketit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Http\Traits\ContentEllipse;

class Configuration extends Model
{
    use ContentEllipse;

    /** @var string */
    protected $table = 'ticketit_settings';

    /** @var array<int, string> */
    protected $fillable = ['lang', 'slug', 'value', 'default'];

    /**
     * S6600: Laravel 10+ recomenda o uso de classes de Attribute para Mutators.
     * S1142: Ponto de saída único e simplificado.
     */
    protected function lang(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => !empty(trim((string) $value)) ? $value : null,
        );
    }

    /**
     * Tipagem nativa via Casts.
     * @var array<string, string>
     */
    protected $casts = [
        'id'      => 'integer',
        'lang'    => 'string',
        'slug'    => 'string',
        'value'   => 'string',
        'default' => 'string',
    ];
}
