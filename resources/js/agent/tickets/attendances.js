/**
 * Alpine.js component para o histórico de atendimentos de um ticket.
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('attendanceManager', attendanceManager)
 *
 * Funcionalidades:
 *  - Lista de atendimentos carregada via API
 *  - Busca por texto (notas)
 *  - Filtro por canal de retorno (Zap, Tel, Cel)
 *  - Filtro por período (data inicial / data final)
 *  - Formulário de novo atendimento com canais de retorno
 */
export function attendanceManager(ticketId, options = {}) {
    return {
        ticketId,
        agents: options.agents || [],
        attendances: [],
        loading: true,
        submitting: false,

        // ── Formulário de novo atendimento ────────────────────────────────
        form: {
            notes:     '',
            returnZap: false,
            returnTel: false,
            returnCel: false,
            returnUserId: '',
            returnAt: '',
        },

        // ── Filtros ───────────────────────────────────────────────────────
        searchText:  '',
        filterZap:   false,
        filterTel:   false,
        filterCel:   false,
        filterFrom:  '',
        filterTo:    '',
        showFilters: false,

        // ── Computed: lista filtrada ──────────────────────────────────────
        get filtered() {
            return this.attendances.filter(a => {
                if (this.searchText.trim()) {
                    const term = this.searchText.toLowerCase();
                    if (!a.notes?.toLowerCase().includes(term)) return false;
                }
                if (this.filterZap && !a.return_zap) return false;
                if (this.filterTel && !a.return_tel) return false;
                if (this.filterCel && !a.return_cel) return false;
                if (this.filterFrom) {
                    const from = new Date(this.filterFrom + 'T00:00:00');
                    if (new Date(a.created_at) < from) return false;
                }
                if (this.filterTo) {
                    const to = new Date(this.filterTo + 'T23:59:59');
                    if (new Date(a.created_at) > to) return false;
                }
                return true;
            });
        },

        get hasActiveFilters() {
            return this.searchText || this.filterZap || this.filterTel || this.filterCel || this.filterFrom || this.filterTo;
        },

        // ── Inicialização ─────────────────────────────────────────────────
        init() {
            this.loadData();
        },

        clearFilters() {
            this.searchText = '';
            this.filterZap  = false;
            this.filterTel  = false;
            this.filterCel  = false;
            this.filterFrom = '';
            this.filterTo   = '';
        },

        // ── API ───────────────────────────────────────────────────────────
        loadData() {
            this.loading = true;
            fetch(`/api/v1/tickets/${this.ticketId}/attendances`, {
                headers: { 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(json => {
                this.attendances = json.data || [];
                this.loading     = false;
            })
            .catch(() => {
                console.error('Erro ao carregar atendimentos');
                this.loading = false;
            });
        },

        submit() {
            if (this.form.notes.trim() === '') {
                return Promise.resolve();
            }

            const hasReturn = this.form.returnZap || this.form.returnTel || this.form.returnCel;

            if (!hasReturn) {
                this.form.returnUserId = '';
                this.form.returnAt     = '';
            }

            this.submitting = true;
            return fetch(`/api/v1/tickets/${this.ticketId}/attendances`, {
                method:  'POST',
                headers: {
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    notes:      this.form.notes,
                    return_zap: this.form.returnZap ? 1 : 0,
                    return_tel: this.form.returnTel ? 1 : 0,
                    return_cel: this.form.returnCel ? 1 : 0,
                    return_user_id: this.form.returnUserId || null,
                    return_at: this.form.returnAt || null,
                }),
            })
            .then(r => r.json().then(json => ({ ok: r.ok, json })))
            .then(({ ok, json }) => {
                if (!ok) throw new Error(json.message || 'Erro ao salvar atendimento.');
                this.form.notes     = '';
                this.form.returnZap = false;
                this.form.returnTel = false;
                this.form.returnCel = false;
                this.form.returnUserId = '';
                this.form.returnAt     = '';
                this.loadData();
                this.submitting = false;
            })
            .catch(err => {
                alert(err.message || 'Erro ao salvar atendimento. Verifique sua conexão.');
                this.submitting = false;
            });
        },

        // ── Utilitário de data ────────────────────────────────────────────
        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },
    };
}
