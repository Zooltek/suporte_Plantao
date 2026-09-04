/**
 * Alpine.js component para o histórico de modificações de um ticket (auditoria).
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('ticketAudit', ticketAudit)
 */
export function ticketAudit(ticketId) {
    return {
        ticketId,
        audits:   [],
        loading:  true,
        error:    null,

        // ── Filtros ───────────────────────────────────────────────────────
        filterEvent: '',
        searchUser:  '',
        onlyDepartment: false,

        get filtered() {
            const deptEvents = ['department_changed', 'department_backfill'];
            return this.audits.filter(a => {
                if (this.onlyDepartment && !deptEvents.includes(a.event)) return false;
                if (this.filterEvent && a.event !== this.filterEvent) return false;
                if (this.searchUser.trim()) {
                    const term = this.searchUser.toLowerCase();
                    if (!a.user?.name?.toLowerCase().includes(term)) return false;
                }
                return true;
            });
        },

        toggleOnlyDepartment() {
            this.onlyDepartment = !this.onlyDepartment;
        },

        get eventTypes() {
            const events = [...new Set(this.audits.map(a => a.event))];
            return events;
        },

        init() {
            this.loadData();
        },

        loadData() {
            this.loading = true;
            this.error   = null;
            fetch(`/api/v1/tickets/${this.ticketId}/audits`, {
                headers: { 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(json => {
                this.audits  = json.data || [];
                this.loading = false;
            })
            .catch(() => {
                this.error   = 'Erro ao carregar histórico de modificações.';
                this.loading = false;
            });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },

        eventIcon(event) {
            const briefcase = 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
            const icons = {
                created:              'M12 4v16m8-8H4',
                status_changed:       'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                agent_changed:        'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                department_changed:   briefcase,
                department_backfill:  briefcase,
                field_changed:        'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5',
                closed:               'M5 13l4 4L19 7',
                captured:             'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            };
            return icons[event] || icons['field_changed'];
        },

        eventColor(event) {
            const colors = {
                created:              'bg-emerald-100 text-emerald-700',
                status_changed:       'bg-blue-100 text-blue-700',
                agent_changed:        'bg-violet-100 text-violet-700',
                department_changed:   'bg-indigo-100 text-indigo-700',
                department_backfill:  'bg-cyan-100 text-cyan-700',
                field_changed:        'bg-gray-100 text-gray-600',
                closed:               'bg-rose-100 text-rose-700',
                captured:             'bg-amber-100 text-amber-700',
            };
            return colors[event] || colors['field_changed'];
        },
    };
}
