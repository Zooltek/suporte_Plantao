import assert from 'node:assert/strict';
import test from 'node:test';

import { ticketForm } from './form.js';

function buildComponent(overrides = {}) {
    return ticketForm({
        selectedCompany: '10',
        selectedModule: '',
        selectedRatModule: '',
        initialVersion: '',
        initialRelease: '',
        versionCatalog: ['01.28.01', '01.32.01'],
        companies: [
            {
                id: 10,
                is_active: true,
                trade_name: 'Cliente A',
                module_types: [
                    {
                        id: 101,
                        name: 'Aplicativo',
                        rat_template_id: 701,
                        rat_template_name: 'Financeiro',
                        rat_template_project: 'ERP',
                        rat_template_item_count: 2,
                    },
                    {
                        id: 102,
                        name: 'E-commerce',
                        rat_template_id: null,
                        rat_template_name: null,
                        rat_template_project: null,
                        rat_template_item_count: 0,
                    },
                ],
                schedule_rat_modules: [
                    { id: 801, name: 'Cadastro', project: 'EasyMaster', element_count: 4 },
                    { id: 802, name: 'Estoque', project: 'EasyMaster', element_count: 3 },
                ],
            },
            {
                id: 20,
                is_active: true,
                trade_name: 'Cliente B',
                module_types: [
                    {
                        id: 201,
                        name: 'Uninf Rede',
                        rat_template_id: null,
                        rat_template_name: null,
                        rat_template_project: null,
                        rat_template_item_count: 0,
                    },
                ],
                schedule_rat_modules: [],
            },
        ],
        availableRatModules: [
            { id: 901, name: 'Financeiro Global', project: 'ERP', element_count: 5 },
            { id: 902, name: 'Contábil Global', project: 'ERP', element_count: 6 },
        ],
        technicalContexts: {
            10: {
                software_name: 'EasyMaster',
                software_version: '01.32.01',
                suggested_version: '01.30.01',
                suggested_release: 'R10',
                has_suggestion: true,
                latest_context_at: '24/03/2026 10:30',
            },
            20: {
                software_name: 'AmuraWeb',
                software_version: '03.05.00',
                suggested_version: '03.05.00',
                suggested_release: 'WEB-22',
                has_suggestion: true,
                latest_context_at: '24/03/2026 11:00',
            },
        },
        ...overrides,
    });
}

test('não sobrescreve versão e release iniciais sem ação explícita do usuário', () => {
    const component = buildComponent({
        initialVersion: '01.28.01',
        initialRelease: 'R1',
    });

    assert.equal(component.versionInput, '01.28.01');
    assert.equal(component.releaseInput, 'R1');
});

test('resolve o último contexto salvo do cliente selecionado', () => {
    const component = buildComponent();

    assert.deepEqual(component.resolvedTechnicalContextForCompany(), {
        version: '01.30.01',
        release: 'R10',
        source: 'history',
    });
});

test('carrega automaticamente o contexto do cliente ao trocar a empresa', () => {
    const component = buildComponent({
        selectedCompany: '',
    });

    component.handleSelectedCompanyChange('10');

    assert.equal(component.versionInput, '01.30.01');
    assert.equal(component.releaseInput, 'R10');
});

test('substitui o contexto pelos últimos dados do cliente recém-selecionado', () => {
    const component = buildComponent();

    component.handleSelectedCompanyChange('10');
    component.handleSelectedCompanyChange('20', '10');

    assert.equal(component.versionInput, '03.05.00');
    assert.equal(component.releaseInput, 'WEB-22');
});

test('restaura o contexto salvo do cliente após edição manual', () => {
    const component = buildComponent();

    component.handleSelectedCompanyChange('10');
    component.versionInput = '01.31.99';
    component.releaseInput = 'HOTFIX';

    assert.equal(component.hasRestorableTechnicalContext(), true);

    component.restoreTechnicalContext();

    assert.equal(component.versionInput, '01.30.01');
    assert.equal(component.releaseInput, 'R10');
});

test('usa a versão cadastrada do software quando não existe histórico salvo', () => {
    const component = buildComponent({
        selectedCompany: '30',
        technicalContexts: {
            30: {
                software_name: 'Plenus',
                software_version: '04.01.00',
                suggested_version: '',
                suggested_release: '',
                has_suggestion: false,
                latest_context_at: null,
            },
        },
    });

    assert.deepEqual(component.resolvedTechnicalContextForCompany(), {
        version: '04.01.00',
        release: '',
        source: 'software',
    });
});

test('mescla catálogo global com contexto técnico do cliente sem duplicidades', () => {
    const component = buildComponent({
        selectedCompany: '20',
        versionCatalog: ['01.28.01', '03.05.00'],
    });

    assert.deepEqual(component.availableVersionOptions(), ['01.28.01', '03.05.00']);
});

test('usa o template RAT vinculado ao módulo contratado quando ele existe', async () => {
    const originalFetch = global.fetch;
    global.fetch = async () => ({
        ok: true,
        async json() {
            return {
                Geral: [{ id: 1, name: 'rat_financeiro', label: 'Financeiro', type: 'checkbox' }],
            };
        },
    });

    try {
        const component = buildComponent({
            selectedModule: '101',
        });

        await component.handleSelectedModuleChange();

        assert.equal(component.selectedRatModule, '701');
        assert.equal(component.ratGroups.length, 1);
        assert.equal(component.ratGroups[0].name, 'Geral');
        assert.equal(component.ratFeedbackState, 'idle');
    } finally {
        global.fetch = originalFetch;
    }
});

test('solicita seleção manual quando o módulo não possui template padrão e há múltiplas opções', async () => {
    const component = buildComponent({
        selectedModule: '102',
    });

    await component.handleSelectedModuleChange();

    assert.equal(component.selectedRatModule, '');
    assert.equal(component.ratFeedbackState, 'select_template');
});

test('usa o fallback global de checklists RAT quando o cliente não tem módulos técnicos atribuídos', () => {
    const component = buildComponent({
        selectedCompany: '20',
    });

    assert.deepEqual(
        component.selectedCompanyRatModules().map(module => module.id),
        [901, 902]
    );
});

test('exibe aviso quando o template RAT selecionado não possui itens', async () => {
    const originalFetch = global.fetch;
    global.fetch = async () => ({
        ok: true,
        async json() {
            return {};
        },
    });

    try {
        const component = buildComponent({
            selectedModule: '102',
            selectedRatModule: '801',
        });

        await component.handleSelectedRatTemplateChange('801');

        assert.equal(component.ratGroups.length, 0);
        assert.equal(component.ratFeedbackState, 'empty_template');
    } finally {
        global.fetch = originalFetch;
    }
});
