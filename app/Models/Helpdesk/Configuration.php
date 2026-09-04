<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ContentEllipse;

class Configuration extends Model
{
    //use ContentEllipse;

    protected $table = 'ticketit_settings';

    protected $fillable = [
        'lang',
        'slug',
        'value',
        'default',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'id'      => 'integer',
        'lang'    => 'string',
        'slug'    => 'string',
        'value'   => 'string',
        'default' => 'string',
    ];

    /**
     * Nullify lang column if empty.
     */
    public function setLangAttribute($lang): void
    {
        $this->attributes['lang'] = trim((string) $lang) !== '' ? $lang : null;
    }
}
