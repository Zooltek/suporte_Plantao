import assert from 'node:assert/strict';
import test from 'node:test';

import { categoryManager, categoryRow } from '../../resources/js/admin/category/categories.js';

test('categoryRow preserva departamento ao abrir modal de edição', () => {
    const row = categoryRow({
        category_id: 23,
        priority: 'low',
        department_id: 3,
        description: {
            name: 'Comercial',
            permalink: 'comercial',
            description: 'Chamados comerciais',
        },
        parent_id: 0,
        parent: null,
    });

    const payload = row.buildCategoryPayload();

    assert.equal(payload.category_id, 23);
    assert.equal(payload.department_id, 3);
    assert.equal(payload.description, 'Chamados comerciais');
});

test('categoryManager preenche departamento selecionado no formulário de edição', () => {
    const manager = categoryManager();

    manager.openEditModal({
        category_id: 23,
        name: 'Comercial',
        parent_id: 0,
        priority: 'low',
        permalink: 'comercial',
        description: '',
        department_id: 3,
    });

    assert.equal(manager.editForm.department_id, '3');
});
