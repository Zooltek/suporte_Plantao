export function recordCreate() {
    return {
        submitting: false,
        config: {},

        init() {
            // Keyboard shortcut Ctrl+S / Cmd+S
            document.addEventListener('keydown', (event) => {
                if (event.ctrlKey || event.metaKey) {
                    if (event.key.toLowerCase() === 's') {
                        event.preventDefault();
                        const btn = document.getElementById('confirm-btn');
                        if (btn && !btn.disabled) btn.click();
                    }
                }
            });

            // Refresh parent window on close (popup mode)
            window.addEventListener('pagehide', () => {
                if (window.opener && typeof window.opener.refresh === 'function') {
                    window.opener.refresh();
                }
            });
        },

        initData(data) {
            this.config = data;

            if (this.config.moduleId) this.loadElements(this.config.moduleId);
            if (this.config.customerId) this.loadHistory(this.config.customerId);
        },

        loadHistory(id) {
            const container = document.getElementById('history-container');
            if (!container) return;
            if (!id) { container.innerHTML = ''; return; }

            container.innerHTML = '<div class="py-4 flex items-center rounded-xl p-4 shadow-sm border bg-white dark:bg-slate-800 border-gray-100 dark:border-slate-700"><svg class="animate-spin h-5 w-5 text-orange-500 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="text-sm font-semibold text-gray-500 dark:text-slate-400">Verificando histórico...</span></div>';

            let url = `${this.config.baseHistoryUrl}/${id}/history`;
            if (this.config.ticketId) url += `?id=${this.config.ticketId}`;

            // X-Requested-With sinaliza ao controller que a requisição é AJAX
            // e deve devolver o partial em vez da view com @extends('layouts.agent').
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => container.innerHTML = html)
                .catch(() => {
                    container.innerHTML = '<div class="p-3 text-red-500 text-sm font-bold border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800/50 rounded-lg">Erro ao carregar o histórico.</div>';
                });
        },

        loadElements(moduleId) {
            const container = document.getElementById('record-elements-target');
            const loader    = document.getElementById('elements-loading');
            if (!container || !loader) return;

            container.innerHTML = '';
            if (!moduleId) return;

            loader.style.display = 'flex';

            fetch(`${this.config.apiElementsUrl}?module_id=${moduleId}`)
                .then(r => r.json())
                .then(data => {
                    loader.style.display = 'none';

                    Object.keys(data).forEach(groupName => {
                        const elements = data[groupName];
                        let html = `
                            <div class="pt-4">
                                <h4 class="text-base font-bold text-gray-700 dark:text-slate-300 mb-3 border-b border-gray-200 dark:border-slate-700 pb-2 cursor-pointer flex items-center justify-between group" onclick="selectAllChecks(this)">
                                    <span>${groupName}</span>
                                    <span class="text-xs font-normal text-gray-400 dark:text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">Clicar seleciona todos</span>
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        `;
                        elements.forEach(val => { html += this._buildElementHtml(val); });
                        html += `</div></div>`;
                        container.insertAdjacentHTML('beforeend', html);
                    });

                    if (this.config.isEdit && this.config.recordId) {
                        this.setElementsValues(this.config.recordId);
                    }
                })
                .catch(() => { loader.style.display = 'none'; });
        },

        _buildElementHtml(val) {
            const baseId = `el-${val.name}`;
            const cls = 'block w-full py-2 px-3 rounded-lg border-gray-300 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-900 dark:text-slate-100 shadow-inner focus:border-orange-500 focus:ring-orange-500 sm:text-sm transition-colors';

            switch (val.type) {
                case 'checkbox':
                    return `<div class="col-span-1 flex items-start p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center h-5"><input id="${baseId}" name="${val.name}" type="checkbox" class="focus:ring-orange-500 h-4 w-4 text-orange-500 border-gray-300 dark:border-slate-600 rounded"></div>
                        <div class="ml-3 text-sm"><label for="${baseId}" class="font-medium text-gray-700 dark:text-slate-300 cursor-pointer select-none">${val.label}</label></div>
                    </div>`;
                case 'textarea':
                    return `<div class="col-span-full mt-2">
                        <label for="${baseId}" class="block text-xs font-bold text-gray-500 dark:text-slate-400 mb-1">${val.label}</label>
                        <textarea id="${baseId}" name="${val.name}" rows="2" class="${cls}"></textarea>
                    </div>`;
                case 'text':
                    return `<div class="col-span-1 sm:col-span-2">
                        <label for="${baseId}" class="block text-xs font-bold text-gray-500 dark:text-slate-400 mb-1">${val.label}</label>
                        <input type="text" id="${baseId}" name="${val.name}" class="${cls}">
                    </div>`;
                case 'number':
                    return `<div class="col-span-1">
                        <label for="${baseId}" class="block text-xs font-bold text-gray-500 dark:text-slate-400 mb-1">${val.label}</label>
                        <input type="number" min="1" max="100" id="${baseId}" name="${val.name}" class="${cls}">
                    </div>`;
                default:
                    return '';
            }
        },

        setElementsValues(recordId) {
            fetch(`${this.config.apiRecordFetchUrl}/${recordId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(payload => {
                    // API Resource embrulha a resposta em "data"
                    const data = payload.data ?? payload;
                    if (!data.elements) return;
                    data.elements.forEach(val => {
                        const typeName = (val.type ?? val.description)?.name;
                        if (!typeName) return;
                        const el = document.getElementById(`el-${typeName}`);
                        if (!el) return;
                        el.type === 'checkbox' ? (el.checked = true) : (el.value = val.value || '');
                    });
                })
                .catch(e => console.error('Falha ao puxar Record bindings', e));
        },
    };
}

// Vanilla helper — usado pelo onclick inline nos grupos de elementos
window.selectAllChecks = function (headerElement) {
    const grid = headerElement.nextElementSibling;
    if (grid) grid.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = true);
};
