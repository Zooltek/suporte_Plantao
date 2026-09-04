/**
 * Gerenciador de Agendamentos e Clientes
 */
globalThis.ScheduleActions = {
    historyBaseUrl: '/support/company',

    /**
     * carrega o histórico do cliente via AJAX
     */
    async loadHistory(id) {
        if (!id) return;
        const container = document.getElementById('history-container');
        if (!container) return;

        try {
            const response = await fetch(`${this.historyBaseUrl}/${id}/history`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const html = await response.text();
            container.innerHTML = html;
        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
        }
    },

    /**
     * busca cliente no select através do CNPJ
     */
    searchByCnpj() {
        const input = prompt('Buscar cliente por CNPJ');
        if (!input) return;

        const cnpjLimpo = input.replace(/\D/g, '');
        const select = document.getElementById('customer_id_main');
        const options = Array.from(select.options);

        const found = options.find(opt => {
            const optCnpj = opt.getAttribute('data-cnpj') || '';
            return optCnpj.replace(/\D/g, '') === cnpjLimpo;
        });

        if (found) {
            select.value = found.value;
            select.dispatchEvent(new Event('change'));
            this.loadHistory(found.value);
        } else {
            alert('Nenhum cliente encontrado com este CNPJ.');
        }
    }
};

/**
 * atalhos de teclado e eventos globais
 */
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey) {
        const key = e.key.toLowerCase();
        if (key === 's' || key === 'f') {
            e.preventDefault();
            const btn = document.getElementById('confirm-btn');
            if (btn) btn.click();
        }
    }
});

window.addEventListener('beforeunload', () => {
    if (window.opener && typeof window.opener.refresh === 'function') {
        window.opener.refresh();
    }
});
