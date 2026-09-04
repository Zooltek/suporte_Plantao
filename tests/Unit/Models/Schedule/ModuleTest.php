<?php

use App\Models\Schedule\Module;
use App\Models\Schedule\ElementGroup;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('Module — fillable e estrutura', function () {

    it('contém project no array fillable', function () {
        $module = new Module();
        expect($module->getFillable())->toContain('project');
    });

    it('contém name e description no array fillable', function () {
        $module = new Module();
        expect($module->getFillable())
            ->toContain('name')
            ->toContain('description');
    });

    it('a tabela do model é schedule_record_module', function () {
        expect((new Module())->getTable())->toBe('schedule_record_module');
    });

    it('não usa timestamps', function () {
        expect((new Module())->usesTimestamps())->toBeFalse();
    });

    it('elementGroups retorna instância de HasMany', function () {
        $module = new Module();
        expect($module->elementGroups())->toBeInstanceOf(HasMany::class);
    });

});

describe('Module — persistência', function () {

    it('cria module com project preenchido', function () {
        $module = Module::create(['name' => 'Estoque', 'project' => 'EasyMaster']);

        expect($module->name)->toBe('Estoque')
            ->and($module->project)->toBe('EasyMaster');

        $this->assertDatabaseHas('schedule_record_module', [
            'name'    => 'Estoque',
            'project' => 'EasyMaster',
        ]);
    });

    it('cria module sem project (null por padrão)', function () {
        $module = Module::create(['name' => 'Geral']);

        expect($module->project)->toBeNull();

        $this->assertDatabaseHas('schedule_record_module', [
            'name'    => 'Geral',
            'project' => null,
        ]);
    });

    it('atualiza project existente', function () {
        $module = Module::create(['name' => 'Financeiro', 'project' => null]);

        $module->update(['project' => 'AmuraWeb']);

        $this->assertDatabaseHas('schedule_record_module', [
            'id'      => $module->id,
            'project' => 'AmuraWeb',
        ]);
    });

    it('agrupa modules por project corretamente', function () {
        Module::create(['name' => 'Módulo A', 'project' => 'EasyMaster']);
        Module::create(['name' => 'Módulo B', 'project' => 'EasyMaster']);
        Module::create(['name' => 'Módulo C', 'project' => null]);

        $grouped = Module::all()->groupBy(fn ($m) => $m->project ?? 'Geral');

        expect($grouped->keys()->toArray())->toContain('EasyMaster', 'Geral')
            ->and($grouped['EasyMaster'])->toHaveCount(2)
            ->and($grouped['Geral'])->toHaveCount(1);
    });

    it('ordena por project e depois por name', function () {
        Module::create(['name' => 'Zebra', 'project' => 'B']);
        Module::create(['name' => 'Alpha', 'project' => 'B']);
        Module::create(['name' => 'Mango', 'project' => 'A']);

        $modules = Module::orderBy('project')->orderBy('name')->get();

        expect($modules->pluck('name')->toArray())
            ->toBe(['Mango', 'Alpha', 'Zebra']);
    });

});
