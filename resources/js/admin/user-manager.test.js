import assert from 'node:assert/strict';
import test from 'node:test';

import { resolveUserRole, userManager, userRow } from './user-manager.js';

test('resolveUserRole preserva a role explícita recebida do backend', () => {
    assert.equal(resolveUserRole({ role: 1 }), '1');
    assert.equal(resolveUserRole({ role: '2' }), '2');
});

test('userRow usa a role explícita ao abrir a edição mesmo sem flags legadas', () => {
    const row = userRow(10, {
        role: 1,
        department_id: 5,
        name: 'Agente',
        email: 'agente@example.com',
    });

    assert.equal(row.formData.role, '1');
    assert.equal(row.formData.department_id, '5');
});

test('userRow infere crm pelo departamento quando necessário', () => {
    const row = userRow(11, {
        department_id: 3,
        name: 'CRM',
        email: 'crm@example.com',
    });

    assert.equal(row.formData.role, '3');
});

test('openCreateModal restaura o formulário para o estado inicial consistente', () => {
    const manager = userManager({ defaultDepartmentId: 2, crmDepartmentId: 3 });

    manager.createForm.name = 'Teste';
    manager.createForm.email = 'teste@example.com';
    manager.createForm.password = '123456';
    manager.createForm.role = '3';
    manager.createForm.department_id = '3';

    manager.openCreateModal();

    assert.equal(manager.createForm.role, '1');
    assert.equal(manager.createForm.department_id, '2');
    assert.equal(manager.createForm.name, '');
    assert.equal(manager.createForm.email, '');
    assert.equal(manager.createForm.password, '');
});
