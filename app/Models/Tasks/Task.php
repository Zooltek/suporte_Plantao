<?php

declare(strict_types=1);

namespace App\Models\Tasks;

use App\Models\Customer;
use App\Models\Schedule\Record;
use App\Models\Software;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

class Task extends Model
{
    use Searchable, HasFactory;

    protected $table = 'tasks';

    public $timestamps = true;

    public const CLASS_BUG         = 'bug';
    public const CLASS_IMPROVEMENT = 'improvement';
    public const CLASS_FIX         = 'fix';

    public const CLASSIFICATIONS = [
        self::CLASS_BUG         => 'Erro',
        self::CLASS_IMPROVEMENT => 'Melhoria',
        self::CLASS_FIX         => 'Correção',
    ];

    protected $fillable = [
        'title',
        'content',
        'status',
        'classification',
        'user_id',
        'author_id',
        'tester_id',
        'project_id',
        'customer_id',
        'software_id',
        'parent_id',
        'delivery_at',
        'changelog_date',
        'request_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'delivery_at'    => 'datetime',
        'changelog_date' => 'datetime',
        'request_at'     => 'datetime',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    /**
     * Configuração do Laravel Scout.
     * Define quais campos serão indexados para busca.
     */
    public function toSearchableArray(): array
    {
        return [
            'id'      => (int) $this->id,
            'title'   => $this->title,
            'content' => $this->content,
            'status'  => $this->status,
        ];
    }

    // --- Escopos ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['can', 'bin', 'rej']);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', 'bin');
    }

    public function scopeMine(Builder $query): Builder
    {
        $userId = auth('admin')->id() ?? Auth::id();

        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('tester_id', $userId)
              ->orWhere('author_id', $userId);
        });
    }

    /**
     * Ordena: abertas primeiro (new > pen > pro > sto), depois finalizadas/canceladas.
     * Dentro de cada grupo: mais recentes no topo.
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE status
                WHEN 'new' THEN 1
                WHEN 'pen' THEN 2
                WHEN 'pro' THEN 3
                WHEN 'sto' THEN 4
                WHEN 'tdo' THEN 5
                WHEN 'don' THEN 6
                WHEN 'rej' THEN 7
                WHEN 'can' THEN 8
                WHEN 'bin' THEN 9
                ELSE 10
            END ASC
        ")->orderByDesc('created_at');
    }

    // --- Relações ---

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_task');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function childs(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function ratRecords(): BelongsToMany
    {
        return $this->belongsToMany(Record::class, 'schedule_record_task', 'task_id', 'record_id')
            ->withTimestamps();
    }

    public function references(): HasMany
    {
        return $this->hasMany(TaskReference::class, 'task_id');
    }

    public function referenced(): HasMany
    {
        return $this->hasMany(TaskReference::class, 'reference_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- Lógica de Permissões (canModify) ---

    public function canModify(): bool
    {
        $user = auth('admin')->user() ?? Auth::user();

        if (!$user) {
            return false;
        }

        if (in_array((int) $user->id, [23, 34, 40], true)) {
            return true;
        }

        $canModify = ($this->parent_id === null)
            ? $this->checkParentPermissions((int) $user->id)
            : $this->checkSubtaskPermissions((int) $user->id);

        $isRestrictedStatus = $this->status !== 'pen';
        $isNotResponsible = !in_array((int) $user->id, [$this->user_id, $this->tester_id], true);

        if ($canModify && $isRestrictedStatus && $isNotResponsible) {
            $canModify = false;
        }

        return $canModify;
    }

    private function checkParentPermissions(int $userId): bool
    {
        $involved = [(int) $this->author_id, (int) $this->user_id, (int) $this->tester_id];

        if (!in_array($userId, $involved, true)) {
            return false;
        }

        $isDone = $this->status === 'don';
        $isNotDirectlyResponsible = !in_array($userId, [(int) $this->user_id, (int) $this->tester_id], true);

        return !($isDone && $isNotDirectlyResponsible);
    }

    private function checkSubtaskPermissions(int $userId): bool
    {
        $parentUserId = (int) $this->parent?->user_id;
        $involved = [$parentUserId, (int) $this->user_id, (int) $this->author_id];

        return in_array($userId, $involved, true);
    }

    // --- Helpers ---

    public function isCompleted(): bool
    {
        return $this->status === 'don';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'can';
    }

    public function isOwner(int $userId): bool
    {
        return (int) $this->user_id === $userId;
    }

    public function notChangeable(): bool
    {
        return in_array($this->status, ['don', 'rej', 'can'], true);
    }

    protected static function newFactory()
    {
        return \Database\Factories\Tasks\TaskFactory::new();
    }
}
