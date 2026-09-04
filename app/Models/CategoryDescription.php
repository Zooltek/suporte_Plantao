<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryDescription extends Model
{
    use HasFactory;

    protected $table = 'solutions_category_description';
    protected $primaryKey = 'category_id';

    public $timestamps = false;

    protected $fillable = [
        'category_id',
        'name',
        'permalink',
        'description',
    ];

    /**
     * Relacionamento: descrição pertence a uma categoria.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }
}
