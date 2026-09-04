<?php

use App\Models\Department;
use App\Models\Tasks\Label;
use App\Models\User;
use App\Services\Admin\DepartmentService;

describe('DepartmentService', function () {

    beforeEach(function () {
        $this->service = app(DepartmentService::class);
    });

    it('faz soft delete ao excluir departamento', function () {
        Department::factory()->asDefault()->create();
        $dept = Department::factory()->create(['name' => 'TI']);

        $this->service->deleteDepartment($dept);

        $this->assertSoftDeleted('user_department', ['id' => $dept->id]);
    });

    it('move labels órfãos para o departamento padrão ao excluir', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = Department::factory()->create(['name' => 'Vendas']);
        $label   = Label::factory()->create(['department_id' => $dept->id, 'parent_id' => null]);

        $this->service->deleteDepartment($dept);

        expect($label->fresh()->department_id)->toBe($default->id);
    });

    it('move usuários para o departamento padrão ao excluir', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = Department::factory()->create(['name' => 'Suporte']);
        $user    = User::factory()->create(['department_id' => $dept->id]);

        $this->service->deleteDepartment($dept);

        expect($user->fresh()->department_id)->toBe($default->id);
    });

    it('reutiliza o departamento padrão existente ao excluir', function () {
        $default = Department::factory()->asDefault()->create();
        $dept    = Department::factory()->create(['name' => 'Suporte']);
        $label   = Label::factory()->create(['department_id' => $dept->id, 'parent_id' => null]);

        $this->service->deleteDepartment($dept);

        expect(Department::where('is_default', true)->count())->toBe(1);
        expect($label->fresh()->department_id)->toBe($default->id);
    });

    it('lança InvalidArgumentException ao tentar excluir o departamento padrão', function () {
        $default = Department::factory()->asDefault()->create();

        expect(fn () => $this->service->deleteDepartment($default))
            ->toThrow(\InvalidArgumentException::class, 'O departamento padrão não pode ser excluído.');
    });

    it('não soft-deleta o departamento padrão ao reutilizá-lo como fallback', function () {
        $default = Department::factory()->asDefault()->create();
        $outro   = Department::factory()->create(['name' => 'Outro']);

        $this->service->deleteDepartment($outro);

        expect(Department::where('is_default', true)->withTrashed()->count())->toBe(1);
        expect(Department::where('is_default', true)->first())->not->toBeNull();
    });

    it('findDefaultDepartment retorna o departamento com is_default true', function () {
        $default = Department::factory()->asDefault()->create();

        $result = $this->service->findDefaultDepartment();

        expect($result->id)->toBe($default->id);
        expect($result->is_default)->toBeTrue();
    });

    it('getAllDepartments não retorna departamentos excluídos', function () {
        Department::factory()->create(['name' => 'Ativo']);
        $excluido = Department::factory()->create(['name' => 'Excluido']);
        $excluido->delete();

        $result = $this->service->getAllDepartments();

        expect($result->pluck('name'))->toContain('Ativo')
            ->not->toContain('Excluido');
    });

});
