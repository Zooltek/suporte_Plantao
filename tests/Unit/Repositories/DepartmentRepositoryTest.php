<?php

use App\Models\Department;
use App\Models\Tasks\Label;
use App\Models\User;
use App\Repositories\DepartmentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dept_repo(): DepartmentRepository
{
    return new DepartmentRepository();
}

function make_dept(array $attrs = []): Department
{
    return Department::factory()->create($attrs);
}

function make_label(array $attrs = []): Label
{
    return Label::factory()->create($attrs);
}

// ─── getAllWithLabels ─────────────────────────────────────────────────────────

describe('DepartmentRepository — getAllWithLabels', function () {

    it('retorna departamentos ordenados por nome', function () {
        make_dept(['name' => 'Suporte']);
        make_dept(['name' => 'Atendimento']);
        make_dept(['name' => 'Marketing']);

        $result = dept_repo()->getAllWithLabels();

        $names = $result->pluck('name')->toArray();
        $sorted = $names;
        sort($sorted);

        expect($names)->toBe($sorted);
    });

    it('eager-loads labels com filtro active e sem parent', function () {
        $dept        = make_dept(['name' => 'TI']);
        $activeLabel = make_label(['department_id' => $dept->id, 'parent_id' => null, 'is_active' => true]);
        $inactiveLabel = make_label(['department_id' => $dept->id, 'parent_id' => null, 'is_active' => false]);
        $childLabel  = make_label(['department_id' => $dept->id, 'parent_id' => $activeLabel->id, 'is_active' => true]);

        $result = dept_repo()->getAllWithLabels();

        $found = $result->firstWhere('id', $dept->id);
        expect($found)->not->toBeNull();
        expect($found->relationLoaded('labels'))->toBeTrue();

        $labelIds = $found->labels->pluck('id');
        expect($labelIds)->toContain($activeLabel->id);
        expect($labelIds)->not->toContain($inactiveLabel->id);
        expect($labelIds)->not->toContain($childLabel->id);
    });

});

// ─── findWithLabels ───────────────────────────────────────────────────────────

describe('DepartmentRepository — findWithLabels', function () {

    it('retorna o departamento com labels carregadas', function () {
        $dept  = make_dept(['name' => 'Vendas']);
        $label = make_label(['department_id' => $dept->id, 'parent_id' => null, 'is_active' => true]);

        $result = dept_repo()->findWithLabels($dept->id);

        expect($result->id)->toBe($dept->id);
        expect($result->relationLoaded('labels'))->toBeTrue();
        expect($result->labels->pluck('id'))->toContain($label->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => dept_repo()->findWithLabels(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── create ──────────────────────────────────────────────────────────────────

describe('DepartmentRepository — create', function () {

    it('persiste o departamento no banco', function () {
        $dept = dept_repo()->create(['name' => 'Financeiro', 'description' => 'Setor financeiro']);

        expect($dept)->toBeInstanceOf(Department::class);
        $this->assertDatabaseHas('user_department', [
            'id'          => $dept->id,
            'name'        => 'Financeiro',
            'description' => 'Setor financeiro',
        ]);
    });

    it('persiste com description nula quando não informada', function () {
        $dept = dept_repo()->create(['name' => 'Sem Desc']);

        $this->assertDatabaseHas('user_department', [
            'id'          => $dept->id,
            'description' => null,
        ]);
    });

});

// ─── update ──────────────────────────────────────────────────────────────────

describe('DepartmentRepository — update', function () {

    it('persiste as alterações no banco', function () {
        $dept = make_dept(['name' => 'Antigo', 'description' => 'Desc antiga']);

        $result = dept_repo()->update($dept, ['name' => 'Novo', 'description' => 'Desc nova']);

        expect($result->name)->toBe('Novo');
        expect($result->description)->toBe('Desc nova');
        $this->assertDatabaseHas('user_department', ['id' => $dept->id, 'name' => 'Novo']);
    });

});

// ─── findDefault ─────────────────────────────────────────────────────────────

describe('DepartmentRepository — findDefault', function () {

    it('retorna o departamento marcado como is_default', function () {
        $default = Department::factory()->asDefault()->create();
        make_dept(['name' => 'Outro']);

        $result = dept_repo()->findDefault();

        expect($result->id)->toBe($default->id);
        expect($result->is_default)->toBeTrue();
    });

    it('cria o departamento Geral se nenhum is_default existir', function () {
        $result = dept_repo()->findDefault();

        expect($result->is_default)->toBeTrue();
        expect($result->name)->toBe('Geral');
        $this->assertDatabaseHas('user_department', ['is_default' => true, 'name' => 'Geral']);
    });

    it('não cria duplicatas ao chamar findDefault múltiplas vezes', function () {
        dept_repo()->findDefault();
        dept_repo()->findDefault();

        expect(Department::where('is_default', true)->count())->toBe(1);
    });

});

// ─── deleteWithFallback ──────────────────────────────────────────────────────

describe('DepartmentRepository — deleteWithFallback', function () {

    it('faz soft-delete do departamento', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = make_dept(['name' => 'A deletar']);

        dept_repo()->deleteWithFallback($dept);

        $this->assertSoftDeleted('user_department', ['id' => $dept->id]);
    });

    it('usa o departamento padrão (is_default) como fallback', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = make_dept(['name' => 'Temp']);

        dept_repo()->deleteWithFallback($dept);

        expect(Department::where('is_default', true)->count())->toBe(1);
        expect(Department::find($default->id))->not->toBeNull();
    });

    it('cria o departamento padrão se não existir nenhum', function () {
        $dept = make_dept(['name' => 'Temp']);

        dept_repo()->deleteWithFallback($dept);

        expect(Department::where('is_default', true)->exists())->toBeTrue();
    });

    it('move labels do departamento deletado para o padrão', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = make_dept(['name' => 'Movendo']);
        $label   = make_label(['department_id' => $dept->id, 'parent_id' => null]);

        dept_repo()->deleteWithFallback($dept);

        expect($label->fresh()->department_id)->toBe($default->id);
    });

    it('move usuários do departamento deletado para o padrão', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = make_dept(['name' => 'Movendo']);
        $user    = User::factory()->create(['department_id' => $dept->id]);

        dept_repo()->deleteWithFallback($dept);

        expect($user->fresh()->department_id)->toBe($default->id);
    });

    it('lança InvalidArgumentException ao tentar deletar o departamento padrão', function () {
        $default = Department::factory()->asDefault()->create();

        expect(fn () => dept_repo()->deleteWithFallback($default))
            ->toThrow(\InvalidArgumentException::class, 'O departamento padrão não pode ser excluído.');
    });

});

// ─── getDepartmentStats ───────────────────────────────────────────────────────

describe('DepartmentRepository — getDepartmentStats', function () {

    it('retorna as chaves total, withModules e empty', function () {
        $stats = dept_repo()->getDepartmentStats();

        expect($stats)->toHaveKeys(['total', 'withModules', 'empty']);
    });

    it('conta corretamente total, withModules e empty', function () {
        $withLabel    = make_dept(['name' => 'Com Label']);
        $withoutLabel = make_dept(['name' => 'Sem Label']);

        make_label(['department_id' => $withLabel->id, 'parent_id' => null, 'is_active' => true]);

        $stats = dept_repo()->getDepartmentStats();

        expect($stats['total'])->toBeGreaterThanOrEqual(2);
        expect($stats['withModules'])->toBeGreaterThanOrEqual(1);
        expect($stats['empty'])->toBeGreaterThanOrEqual(1);
        expect($stats['total'])->toBe($stats['withModules'] + $stats['empty']);
    });

});
