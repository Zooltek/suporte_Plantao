import { describe, it } from 'node:test';
import assert from 'node:assert/strict';

import { ticketForm } from '../../resources/js/agent/tickets/form.js';

function buildComponent(overrides = {}) {
    return ticketForm({
        selectedCompany: '20',
        selectedModule: '201',
        selectedRatModule: '',
        companies: [
            {
                id: 20,
                is_active: true,
                trade_name: 'Cliente sem RAT',
                module_types: [
                    {
                        id: 201,
                        name: 'Caixa Autom.',
                        rat_template_id: null,
                        rat_template_name: null,
                        rat_template_project: null,
                        rat_template_item_count: 0,
                    },
                ],
                schedule_rat_modules: [],
            },
        ],
        availableRatModules: [],
        ...overrides,
    });
}

describe('ticketForm — visibilidade do checklist RAT técnico', () => {
    it('oculta o seletor manual quando o cliente não possui nenhum checklist disponível', async () => {
        const component = buildComponent();

        await component.handleSelectedModuleChange();

        assert.equal(component.showRatTemplateSelector(), false);
        assert.equal(component.ratFeedbackState, 'unavailable');
    });

    it('mantém o seletor manual quando há catálogo RAT disponível para seleção', async () => {
        const component = buildComponent({
            availableRatModules: [
                { id: 901, name: 'Financeiro', project: 'ERP', element_count: 3 },
                { id: 902, name: 'Estoque', project: 'ERP', element_count: 2 },
            ],
        });

        await component.handleSelectedModuleChange();

        assert.equal(component.showRatTemplateSelector(), true);
        assert.equal(component.ratFeedbackState, 'select_template');
    });
});
