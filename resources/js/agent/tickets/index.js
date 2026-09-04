/**
 * Alpine.js component para a listagem de chamados do agente.
 *
 * Separado da view Blade respeitando o princípio SRP e a convenção de JS modular do projeto.
 * Registrado via: Alpine.data('ticketIndex', ticketIndex)
 */
export function ticketIndex(refreshRate = 0) {
    return {
        // ─── Estado dos filtros (mobile) ───────────────────────────────────
        filtersOpen: window.innerWidth >= 1024,
        timer: null,

        init() {
            if (refreshRate >= 5000) {
                this.timer = setInterval(() => {
                    const activeEl = document.activeElement;
                    const isTyping = activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA');
                    if (!isTyping) {
                        window.location.reload();
                    }
                }, refreshRate);
            }
        },

        // ─── Métodos ───────────────────────────────────────────────────────
        toggleFilters() {
            this.filtersOpen = !this.filtersOpen;
        },

        /**
         * Submete o formulário de filtros ao alterar um campo select,
         * dando feedback imediato sem necessidade de clicar em "Aplicar".
         */
        submitOnChange() {
            this.$el.closest('form').submit();
        },

        /**
         * Ajusta rapidamente os inputs de data para períodos comuns.
         */
        setPeriod(preset) {
            const today = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            const formatDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

            const fromEl = document.getElementById('filter-date-from');
            const toEl = document.getElementById('filter-date-to');
            if (!fromEl || !toEl) return;

            if (preset === 'today') {
                fromEl.value = formatDate(today);
                toEl.value = formatDate(today);
            } else if (preset === '7days') {
                const past = new Date();
                past.setDate(today.getDate() - 7);
                fromEl.value = formatDate(past);
                toEl.value = formatDate(today);
            } else if (preset === 'month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                fromEl.value = formatDate(firstDay);
                toEl.value = formatDate(today);
            } else if (preset === 'clear') {
                fromEl.value = '';
                toEl.value = '';
            }

            fromEl.closest('form')?.submit();
        },
    };
}
