import assert from 'node:assert/strict';
import test from 'node:test';

import { mobileTasks } from './mobile-tasks.js';

test('restaura módulo e submódulo a partir das labels antigas do formulário', () => {
    const component = mobileTasks({
        projectModuleTree: {
            5: [
                {
                    id: 10,
                    name: 'Financeiro',
                    childs: [{ id: 11, name: 'Contas a Receber' }],
                },
            ],
        },
        oldForm: {
            project_id: 5,
            labels: ['11'],
        },
    });

    assert.equal(component.selectedProjectId, '5');
    assert.equal(component.selectedModuleId, '10');
    assert.equal(component.selectedSubmoduleId, '11');
});

test('limpa módulo e submódulo quando o projeto é removido', () => {
    const component = mobileTasks({
        projectModuleTree: {
            2: [{ id: 1, name: 'Estoque', childs: [] }],
        },
        oldForm: { project_id: 2, labels: ['1'] },
    });

    component.selectedProjectId = '';
    component.handleProjectChange();

    assert.equal(component.selectedModuleId, '');
    assert.equal(component.selectedSubmoduleId, '');
});

test('limpa módulo e submódulo ao trocar para um projeto sem o módulo anterior', () => {
    const component = mobileTasks({
        projectModuleTree: {
            7: [
                {
                    id: 1,
                    name: 'Comercial',
                    childs: [{ id: 2, name: 'Propostas' }],
                },
            ],
            8: [
                {
                    id: 3,
                    name: 'Fiscal',
                    childs: [],
                },
            ],
        },
        oldForm: { project_id: 7, labels: ['2'] },
    });

    component.selectedProjectId = '8';
    component.handleProjectChange();

    assert.equal(component.selectedModuleId, '');
    assert.equal(component.selectedSubmoduleId, '');
});

test('remove submódulo inválido ao trocar o módulo selecionado', () => {
    const component = mobileTasks({
        projectModuleTree: {
            7: [
                {
                    id: 1,
                    name: 'Comercial',
                    childs: [{ id: 2, name: 'Propostas' }],
                },
                {
                    id: 3,
                    name: 'Fiscal',
                    childs: [],
                },
            ],
        },
        oldForm: { project_id: 7, labels: ['2'] },
    });

    component.selectedModuleId = '3';
    component.handleModuleChange();

    assert.equal(component.selectedSubmoduleId, '');
    assert.deepEqual(component.availableSubmodules(), []);
});

test('sincroniza os filtros da inbox com a URL canônica', () => {
    const previousWindow = global.window;
    const replaceStateCalls = [];

    global.window = {
        location: { href: 'http://localhost:8090/tasks' },
        history: {
            replaceState: (_state, _title, url) => replaceStateCalls.push(url),
        },
        setTimeout: (callback) => {
            callback();
            return 1;
        },
        clearTimeout: () => {},
    };

    try {
        const component = mobileTasks();
        component.search = 'financeiro';
        component.taskIdFilter = '42';
        component.statusFilter = 'done';
        component.classificationFilter = 'fix';
        component.customerFilter = '15';
        component.projectFilter = '9';

        component.syncUrl();

        assert.equal(
            replaceStateCalls.at(-1),
            '/tasks?q=financeiro&task_id=42&status=done&classification=fix&customer_id=15&project_id=9'
        );
    } finally {
        global.window = previousWindow;
    }
});

test('limpa os filtros e remove os parâmetros da URL', () => {
    const previousWindow = global.window;
    const replaceStateCalls = [];

    global.window = {
        location: { href: 'http://localhost:8090/tasks?q=financeiro&status=done' },
        history: {
            replaceState: (_state, _title, url) => replaceStateCalls.push(url),
        },
        setTimeout: (callback) => {
            callback();
            return 1;
        },
        clearTimeout: () => {},
    };

    try {
        const component = mobileTasks();
        component.search = 'financeiro';
        component.taskIdFilter = '42';
        component.statusFilter = 'done';
        component.classificationFilter = 'fix';
        component.customerFilter = '15';
        component.projectFilter = '9';

        component.clearFilters();

        assert.equal(component.search, '');
        assert.equal(component.taskIdFilter, '');
        assert.equal(component.statusFilter, 'open');
        assert.equal(component.classificationFilter, '');
        assert.equal(component.customerFilter, '');
        assert.equal(component.projectFilter, '');
        assert.equal(replaceStateCalls.at(-1), '/tasks');
    } finally {
        global.window = previousWindow;
    }
});

test('filtra por ID da tarefa e pelos novos filtros da inbox', () => {
    const component = mobileTasks();

    component.tasks = [
        {
            id: 77,
            status: 'pen',
            classification: 'fix',
            customer_id: 15,
            project_id: 9,
            title: 'Corrigir conciliação',
            content: 'Ajuste na baixa automática',
            customer: { trade_name: 'Acme' },
            project: { name: 'ERP' },
            user: { name: 'Alice' },
            author: { name: 'Bruno' },
        },
        {
            id: 78,
            status: 'pen',
            classification: 'fix',
            customer_id: 15,
            project_id: 9,
            title: 'Outra tarefa',
            content: 'Sem relação com a busca',
            customer: { trade_name: 'Acme' },
            project: { name: 'ERP' },
            user: { name: 'Alice' },
            author: { name: 'Bruno' },
        },
    ];

    component.search = '#77';
    component.classificationFilter = 'fix';
    component.customerFilter = '15';
    component.projectFilter = '9';
    component.applyFilter();

    assert.deepEqual(component.filtered.map((task) => task.id), [77]);

    component.search = '';
    component.taskIdFilter = '78';
    component.applyFilter();

    assert.deepEqual(component.filtered.map((task) => task.id), [78]);
});

test('mantém o catálogo técnico disponível sem depender de cliente', () => {
    const component = mobileTasks({
        projects: [
            { id: 7, name: 'ERP' },
            { id: 8, name: 'WMS' },
        ],
        projectModuleTree: {
            7: [{ id: 10, name: 'Financeiro', childs: [{ id: 11, name: 'Cobrança' }] }],
            8: [{ id: 20, name: 'Estoque', childs: [] }],
        },
    });

    assert.deepEqual(component.availableProjects(), [
        { id: '7', name: 'ERP' },
        { id: '8', name: 'WMS' },
    ]);

    component.selectedProjectId = '7';
    assert.deepEqual(component.availableModules(), [
        {
            id: '10',
            name: 'Financeiro',
            scheduleModuleId: '',
            childs: [{ id: '11', name: 'Cobrança' }],
        },
    ]);
});
