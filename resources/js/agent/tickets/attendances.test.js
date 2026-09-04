import assert from 'node:assert/strict';
import test from 'node:test';

import { attendanceManager } from './attendances.js';

test('permite registrar retorno realizado sem técnico e sem data', async () => {
    const fetchCalls = [];
    const alerts = [];

    const previousFetch = global.fetch;
    const previousDocument = global.document;
    const previousAlert = global.alert;

    global.document = {
        querySelector() {
            return { content: 'csrf-token' };
        },
    };
    global.alert = (message) => {
        alerts.push(message);
    };
    global.fetch = async (url, options = {}) => {
        fetchCalls.push({ url, options });

        return {
            ok: true,
            async json() {
                return { data: [] };
            },
        };
    };

    try {
        const component = attendanceManager(42);
        component.loadData = () => {};
        component.form.notes = 'Nao atendeu.';
        component.form.returnTel = true;

        await component.submit();

        assert.equal(alerts.length, 0);
        assert.equal(fetchCalls.length, 1);
        assert.equal(fetchCalls[0].options.method, 'POST');

        const payload = JSON.parse(fetchCalls[0].options.body);

        assert.equal(payload.return_tel, 1);
        assert.equal(payload.return_user_id, null);
        assert.equal(payload.return_at, null);
    } finally {
        global.fetch = previousFetch;
        global.document = previousDocument;
        global.alert = previousAlert;
    }
});
