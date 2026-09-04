/**
 * Alpine.js component para múltiplos problemas de um ticket.
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('ticketIssues', ticketIssues)
 */
export function ticketIssues(ticketId) {
    return {
        ticketId,
        issues:     [],
        loading:    true,
        submitting: false,
        resolvingId: null,

        // ── Formulário de novo problema ───────────────────────────────────
        form: {
            title:       '',
            description: '',
        },
        showForm: false,

        // ── Formulário de resolução ───────────────────────────────────────
        resolveForm: {
            id:       null,
            solution: '',
        },
        showResolveFor: null,

        get openCount()    { return this.issues.filter(i => i.status === 'open').length; },
        get resolvedCount(){ return this.issues.filter(i => i.status === 'resolved').length; },

        init() {
            this.loadData();
        },

        loadData() {
            this.loading = true;
            fetch(`/api/v1/tickets/${this.ticketId}/issues`, {
                headers: { 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(json => {
                this.issues  = json.data || [];
                this.loading = false;
            })
            .catch(() => {
                this.loading = false;
            });
        },

        submitIssue() {
            if (!this.form.title.trim()) return;
            this.submitting = true;

            fetch(`/api/v1/tickets/${this.ticketId}/issues`, {
                method:  'POST',
                headers: {
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    title:       this.form.title,
                    description: this.form.description,
                }),
            })
            .then(r => r.json().then(json => ({ ok: r.ok, json })))
            .then(({ ok, json }) => {
                if (!ok) throw new Error(json.message || 'Erro ao registrar problema.');
                this.issues.push(json.data);
                this.form.title       = '';
                this.form.description = '';
                this.showForm         = false;
                this.submitting       = false;
            })
            .catch(() => {
                alert('Erro ao registrar problema.');
                this.submitting = false;
            });
        },

        resolveIssue(issueId) {
            if (!this.resolveForm.solution.trim()) return;
            this.resolvingId = issueId;

            fetch(`/api/v1/tickets/${this.ticketId}/issues/${issueId}/resolve`, {
                method:  'PATCH',
                headers: {
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ solution: this.resolveForm.solution }),
            })
            .then(r => r.json().then(json => ({ ok: r.ok, json })))
            .then(({ ok, json }) => {
                if (!ok) throw new Error(json.message || 'Erro ao resolver problema.');
                const idx = this.issues.findIndex(i => i.id === issueId);
                if (idx !== -1) this.issues[idx] = json.data;
                this.resolveForm.id       = null;
                this.resolveForm.solution = '';
                this.showResolveFor       = null;
                this.resolvingId          = null;
            })
            .catch(() => {
                alert('Erro ao resolver problema.');
                this.resolvingId = null;
            });
        },

        deleteIssue(issueId) {
            if (!confirm('Remover este problema?')) return;

            fetch(`/api/v1/tickets/${this.ticketId}/issues/${issueId}`, {
                method:  'DELETE',
                headers: {
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(r => {
                if (!r.ok) throw new Error('Erro ao remover problema.');
                this.issues = this.issues.filter(i => i.id !== issueId);
            })
            .catch(err => alert(err.message || 'Erro ao remover problema.'));
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },
    };
}
