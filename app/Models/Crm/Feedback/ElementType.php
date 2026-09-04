<?php

declare(strict_types=1);

namespace App\Models\Crm\Feedback;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ElementType extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada ao model.
     * @var string
     */
    protected $table = 'crm_feedback_element_type';

    /**
     * indica que o model não possui colunas de timestamp (created_at/updated_at).
     * @var bool
     */
    public $timestamps = false;

    /**
     * esta lista deve conter exatamente os campos validados no seu Request.
     * * @var array<int, string>
     */
    protected $fillable = [
        'form_id',
        'name',
        'label',
        'type',
        'data',
        'sort_order',
        'status',
    ];

    /**
     * conversão de tipos (Casts) no padrão Laravel 12.
     * * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'form_id'    => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Relacionamento: Um tipo de elemento pode ter vários elementos (respostas).
     */
    public function elements(): HasMany
    {
        return $this->hasMany(Element::class, 'element_id');
    }
}
