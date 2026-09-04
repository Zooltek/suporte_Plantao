<?php

namespace App\Models;

use App\Models\Ticket\Ticket;
use Database\Factories\Helpdesk\Ticketit\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    use HasFactory;

    protected $table = 'solutions_category';

    protected $primaryKey = 'category_id';

    public $timestamps = true;

    protected $fillable = [
        'parent_id',
        'sort_order',
        'status',
        'visible',
        'ticket_category_id',
        'profile',
        'header',
        'priority',
        'department_id',
    ];

    protected $casts = [
        'department_id' => 'integer',
    ];

    // Constantes de prioridade
    public const PRIORITY_LOW = 'low';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Baixa',
        self::PRIORITY_HIGH => 'Alta',
        self::PRIORITY_URGENT => 'Urgente',
    ];

    public const PRIORITY_WEIGHTS = [
        self::PRIORITY_LOW => 1,
        self::PRIORITY_HIGH => 3,
        self::PRIORITY_URGENT => 5,
    ];

    /** Relacionamentos */
    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class, 'category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function description(): HasOne
    {
        return $this->hasOne(CategoryDescription::class, 'category_id', 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id', 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id', 'category_id');
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_category', 'category_id', 'ticket_id');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->where('parent_id', 0);
    }

    public function scopeOrderedByDisplayName(Builder $query): Builder
    {
        return $query
            ->leftJoin(
                'solutions_category_description as category_description_order',
                'category_description_order.category_id',
                '=',
                'solutions_category.category_id'
            )
            ->select('solutions_category.*')
            ->orderBy('category_description_order.name')
            ->orderBy('solutions_category.category_id');
    }

    /** Métodos auxiliares */
    public function getName(): ?string
    {
        return $this->name;
    }

    public static function getNameById(int $id): ?string
    {
        return CategoryDescription::where('category_id', $id)->value('name');
    }

    public function getDescription(): ?string
    {
        return $this->description?->description;
    }

    public function getChildOrdered(string $column = 'name')
    {
        return Category::where('parent_id', $this->category_id)
            ->join('solutions_category_description', 'solutions_category.category_id', '=', 'solutions_category_description.category_id')
            ->orderBy("solutions_category_description.$column", 'asc')
            ->get();
    }

    public function getParentName(): ?string
    {
        return CategoryDescription::where('category_id', $this->parent_id)->value('name');
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value ?? $this->description?->name,
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $name = trim((string) ($this->name ?? ''));

                return $name !== ''
                    ? $name
                    : 'Categoria '.$this->category_id;
            },
        );
    }

    /** Métodos de Prioridade */
    public function getPriorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? 'Baixa';
    }

    public function getPriorityColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'text-red-600',
            self::PRIORITY_HIGH => 'text-yellow-500', // Changed from orange-600 which might not be generated
            self::PRIORITY_LOW => 'text-green-500',
            default => 'text-gray-500',
        };
    }

    public function getPriorityBgColor(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'bg-red-100',
            self::PRIORITY_HIGH => 'bg-yellow-100', // Changed from orange-100
            self::PRIORITY_LOW => 'bg-green-100',
            default => 'bg-gray-100',
        };
    }

    public function getPriorityIcon(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => '🔴',
            self::PRIORITY_HIGH => '🟠',
            self::PRIORITY_LOW => '🟢',
            default => '⚪',
        };
    }

    public function getPriorityWeight(): int
    {
        return self::PRIORITY_WEIGHTS[$this->priority] ?? 0;
    }

    public function calculateTicketWeightWith(?self $subCategory = null): int
    {
        return $this->getPriorityWeight() + ($subCategory?->getPriorityWeight() ?? 0);
    }

    public function getSubcategoriesWeightTotal(): int
    {
        if ($this->relationLoaded('children')) {
            return $this->children->sum(
                static fn (self $child): int => $child->getPriorityWeight()
            );
        }

        return $this->children()
            ->get()
            ->sum(static fn (self $child): int => $child->getPriorityWeight());
    }

    /**
     * Retorna o intervalo possível de peso final por ticket para esta categoria.
     *
     * @return array{min:int,max:int}
     */
    public function getTicketWeightRange(): array
    {
        $baseWeight = $this->getPriorityWeight();

        if ($this->relationLoaded('children')) {
            $children = $this->children;
        } else {
            $children = $this->children()->get();
        }

        if ($children->isEmpty()) {
            return [
                'min' => $baseWeight,
                'max' => $baseWeight,
            ];
        }

        $childWeights = $children->map(
            static fn (self $child): int => $child->getPriorityWeight()
        );

        return [
            'min' => $baseWeight + $childWeights->min(),
            'max' => $baseWeight + $childWeights->max(),
        ];
    }

    public static function getPriorityWeightByLevel(?string $priority): int
    {
        return self::PRIORITY_WEIGHTS[$priority] ?? 0;
    }

    public function ticketitTickets(): HasMany
    {
        return $this->hasMany(\App\Models\Ticket\Ticket::class, 'category_id', 'category_id');
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Ticket\Agent::class,
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
