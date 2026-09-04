<?php

/**
 * Testes de integração do LabelRepository — SQLite in-memory via RefreshDatabase.
 * Cada teste exercita a implementação real contra o banco.
 */

use App\Models\Tasks\Label;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskLabel;
use App\Models\User;
use App\Repositories\LabelRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function lr_repo(): LabelRepository
{
    return new LabelRepository();
}

function lr_label(array $attrs = []): Label
{
    return Label::factory()->create($attrs);
}

function lr_task(array $attrs = []): Task
{
    $user = User::factory()->create();

    return Task::factory()->create(array_merge([
        'user_id'   => $user->id,
        'author_id' => $user->id,
        'status'    => 'pen',
    ], $attrs));
}

// ─── getModuleLabels ──────────────────────────────────────────────────────────

describe('LabelRepository — getModuleLabels', function () {

    it('retorna labels ativas raiz sem user_id e sem department_id quando departmentId é null', function () {
        lr_label(['department_id' => null, 'user_id' => null, 'parent_id' => null]);
        lr_label(['department_id' => null, 'user_id' => null, 'parent_id' => null]);
        // label com user_id deve ser ignorada
        $user = User::factory()->create();
        lr_label(['user_id' => $user->id, 'parent_id' => null, 'department_id' => null]);

        $result = lr_repo()->getModuleLabels(null);

        expect($result->count())->toBe(2)
            ->and($result->every(fn($l) => $l->user_id === null))->toBeTrue();
    });

    it('retorna labels do departamento e labels sem departamento quando departmentId é fornecido', function () {
        $deptId = 99;
        $labelInDept  = lr_label(['department_id' => $deptId,  'user_id' => null, 'parent_id' => null]);
        $labelNoDept  = lr_label(['department_id' => null,     'user_id' => null, 'parent_id' => null]);
        $labelOtherDept = lr_label(['department_id' => 55,     'user_id' => null, 'parent_id' => null]);

        $result = lr_repo()->getModuleLabels($deptId);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->toContain($labelInDept->id)
            ->and($ids)->toContain($labelNoDept->id)
            ->and($ids)->not->toContain($labelOtherDept->id);
    });

    it('não retorna labels inativas', function () {
        lr_label(['is_active' => false, 'department_id' => null, 'user_id' => null, 'parent_id' => null]);

        $result = lr_repo()->getModuleLabels(null);

        expect($result->count())->toBe(0);
    });

    it('carrega childs ativos via eager loading', function () {
        $parent = lr_label(['department_id' => null, 'user_id' => null, 'parent_id' => null]);
        $child  = lr_label(['parent_id' => $parent->id, 'department_id' => null, 'user_id' => null, 'is_active' => true]);
        lr_label(['parent_id' => $parent->id, 'department_id' => null, 'user_id' => null, 'is_active' => false]);

        $result    = lr_repo()->getModuleLabels(null);
        $parentRow = $result->firstWhere('id', $parent->id);

        expect($parentRow->relationLoaded('childs'))->toBeTrue()
            ->and($parentRow->childs->count())->toBe(1)
            ->and($parentRow->childs->first()->id)->toBe($child->id);
    });

    it('retorna lista ordenada por nome', function () {
        lr_label(['name' => 'Zebra',    'department_id' => null, 'user_id' => null, 'parent_id' => null]);
        lr_label(['name' => 'Abacaxi',  'department_id' => null, 'user_id' => null, 'parent_id' => null]);
        lr_label(['name' => 'Maçã',     'department_id' => null, 'user_id' => null, 'parent_id' => null]);

        $names = lr_repo()->getModuleLabels(null)->pluck('name')->toArray();
        $sorted = $names;
        sort($sorted);

        expect($names)->toBe($sorted);
    });

});

// ─── getPersonalLabels ────────────────────────────────────────────────────────

describe('LabelRepository — getPersonalLabels', function () {

    it('retorna apenas labels ativas do usuário informado', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        lr_label(['user_id' => $user->id,  'parent_id' => null, 'is_active' => true]);
        lr_label(['user_id' => $user->id,  'parent_id' => null, 'is_active' => false]);
        lr_label(['user_id' => $other->id, 'parent_id' => null, 'is_active' => true]);

        $result = lr_repo()->getPersonalLabels($user->id);

        expect($result->count())->toBe(1)
            ->and($result->first()->user_id)->toBe($user->id);
    });

    it('retorna coleção vazia quando usuário não tem labels', function () {
        $user = User::factory()->create();

        expect(lr_repo()->getPersonalLabels($user->id)->count())->toBe(0);
    });

});

// ─── createLabel ──────────────────────────────────────────────────────────────

describe('LabelRepository — createLabel', function () {

    it('persiste a label e retorna a instância com id', function () {
        $label = lr_repo()->createLabel(['name' => 'Nova Etiqueta', 'user_id' => null]);

        expect($label->id)->not->toBeNull()
            ->and($label->name)->toBe('Nova Etiqueta');

        $this->assertDatabaseHas('labels', ['id' => $label->id, 'name' => 'Nova Etiqueta']);
    });

    it('define user_id corretamente', function () {
        $user  = User::factory()->create();
        $label = lr_repo()->createLabel(['name' => 'Label Pessoal', 'user_id' => $user->id]);

        expect($label->user_id)->toBe($user->id);
    });

});

// ─── findLabelOrFail ──────────────────────────────────────────────────────────

describe('LabelRepository — findLabelOrFail', function () {

    it('retorna a label pelo id', function () {
        $label = lr_label();

        $found = lr_repo()->findLabelOrFail($label->id);

        expect($found->id)->toBe($label->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => lr_repo()->findLabelOrFail(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── deactivateLabel ─────────────────────────────────────────────────────────

describe('LabelRepository — deactivateLabel', function () {

    it('persiste is_active=false na label', function () {
        $label = lr_label(['is_active' => true]);

        lr_repo()->deactivateLabel($label);

        $this->assertDatabaseHas('labels', ['id' => $label->id, 'is_active' => false]);
    });

    it('remove todos os TaskLabels vinculados à label', function () {
        $label = lr_label();
        $task  = lr_task();

        TaskLabel::create(['task_id' => $task->id, 'label_id' => $label->id]);

        expect(TaskLabel::where('label_id', $label->id)->count())->toBe(1);

        lr_repo()->deactivateLabel($label);

        expect(TaskLabel::where('label_id', $label->id)->count())->toBe(0);
    });

    it('não afeta TaskLabels de outras labels', function () {
        $label1 = lr_label();
        $label2 = lr_label();
        $task   = lr_task();

        TaskLabel::create(['task_id' => $task->id, 'label_id' => $label1->id]);
        TaskLabel::create(['task_id' => $task->id, 'label_id' => $label2->id]);

        lr_repo()->deactivateLabel($label1);

        expect(TaskLabel::where('label_id', $label2->id)->count())->toBe(1);
    });

});

// ─── findTaskOrFail ───────────────────────────────────────────────────────────

describe('LabelRepository — findTaskOrFail', function () {

    it('retorna a task pelo id', function () {
        $task = lr_task();

        $found = lr_repo()->findTaskOrFail($task->id);

        expect($found->id)->toBe($task->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => lr_repo()->findTaskOrFail(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── labelExistsOnTask ────────────────────────────────────────────────────────

describe('LabelRepository — labelExistsOnTask', function () {

    it('retorna true quando a label já está vinculada', function () {
        $label = lr_label();
        $task  = lr_task();

        TaskLabel::create(['task_id' => $task->id, 'label_id' => $label->id]);

        expect(lr_repo()->labelExistsOnTask($task->id, $label->id))->toBeTrue();
    });

    it('retorna false quando a label não está vinculada', function () {
        $label = lr_label();
        $task  = lr_task();

        expect(lr_repo()->labelExistsOnTask($task->id, $label->id))->toBeFalse();
    });

});

// ─── attachLabelToTask ────────────────────────────────────────────────────────

describe('LabelRepository — attachLabelToTask', function () {

    it('cria o registro TaskLabel e retorna a instância', function () {
        $label = lr_label();
        $task  = lr_task();

        $taskLabel = lr_repo()->attachLabelToTask($task->id, $label->id);

        expect($taskLabel->id)->not->toBeNull()
            ->and($taskLabel->task_id)->toBe($task->id)
            ->and($taskLabel->label_id)->toBe($label->id);

        $this->assertDatabaseHas('label_task', [
            'task_id'  => $task->id,
            'label_id' => $label->id,
        ]);
    });

});
