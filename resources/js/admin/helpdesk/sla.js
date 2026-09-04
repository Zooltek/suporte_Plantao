const STAGE_KEYS = ['attention', 'warning', 'critical'];

export function slaSettingsForm() {
    return {
        rows: [],
        clientErrors: [],
        submitting: false,

        init() {
            const raw = this.$el.dataset.rows;
            this.rows = normalizeRows(raw ? JSON.parse(raw) : []);
        },

        submit(event) {
            if (this.submitting) {
                return;
            }

            if (!this.validateRows()) {
                if (globalThis.AppToast) {
                    globalThis.AppToast.error({
                        message: 'Verifique os tempos de SLA antes de salvar.',
                    });
                }
                return;
            }

            this.submitting = true;
            event.target.submit();
        },

        validateRows() {
            const errors = [];

            this.rows.forEach((row) => {
                const attention = Number(row.attention);
                const warning = Number(row.warning);
                const critical = Number(row.critical);

                if (!(attention > 0 && warning > 0 && critical > 0)) {
                    errors.push(`Todos os tempos da prioridade ${row.label} devem ser maiores que zero.`);
                }

                if (!(attention < warning && warning < critical)) {
                    errors.push(`Na prioridade ${row.label}, use a ordem: Atenção < Aviso < Crítico.`);
                }
            });

            STAGE_KEYS.forEach((stage) => {
                const highValue = Number(this.getRowValue('high', stage));
                const medValue = Number(this.getRowValue('med', stage));
                const lowValue = Number(this.getRowValue('low', stage));

                if (!(highValue <= medValue && medValue <= lowValue)) {
                    errors.push(`No estágio ${this.stageLabel(stage)}, mantenha a ordem: Alta <= Média <= Baixa.`);
                }
            });

            this.clientErrors = unique(errors);
            return this.clientErrors.length === 0;
        },

        getRowValue(level, stage) {
            const row = this.rows.find((item) => item.key === level);
            return row ? row[stage] : null;
        },

        stageLabel(stage) {
            return {
                attention: 'Atenção',
                warning: 'Aviso',
                critical: 'Crítico',
            }[stage] ?? stage;
        },
    };
}

function normalizeRows(rows) {
    return rows.map((row) => ({
        key: row.key,
        label: row.label,
        attention: toNumber(row.attention),
        warning: toNumber(row.warning),
        critical: toNumber(row.critical),
    }));
}

function toNumber(value) {
    const casted = Number(value);
    return Number.isFinite(casted) ? casted : 0;
}

function unique(values) {
    return [...new Set(values)];
}
