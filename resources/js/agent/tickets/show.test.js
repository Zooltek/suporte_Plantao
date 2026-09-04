import assert from 'node:assert/strict';
import test from 'node:test';

import { ticketShow } from './show.js';

function buildComponent(overrides = {}) {
    return ticketShow({
        closeStatuses: [
            { id: '3', name: 'Resolvido', requiresSolution: true },
            { id: '5', name: 'Não Resolvido', requiresSolution: false },
        ],
        ...overrides,
    });
}

test('mantém o encerramento sem seleção inicial quando há mais de um tipo de fechamento', () => {
    const component = buildComponent();

    assert.equal(component.closeStatusId, '');
    assert.equal(component.closeHelperText, 'Selecione como deseja encerrar este chamado.');
});

test('exige solução ao selecionar o encerramento como Resolvido', () => {
    const component = buildComponent();

    component.closeStatusId = '3';

    assert.equal(component.closeRequiresSolution, true);
    assert.equal(component.closeSubmitLabel, 'Confirmar Resolvido e ir para pendências');
});

test('usa helper textual simples ao selecionar Não Resolvido', () => {
    const component = buildComponent({
        initialCloseStatusId: '5',
    });

    assert.equal(component.closeRequiresSolution, false);
    assert.match(component.closeHelperText, /fila de pendências/i);
});
