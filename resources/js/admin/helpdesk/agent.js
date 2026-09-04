export function agentManager() {
    return {
        async deleteAgent(id) {
            const ok = await window.confirmModal({
                title: 'Remover agente?',
                message: 'O agente será desvinculado do helpdesk permanentemente.',
                confirmLabel: 'Remover',
            });
            if (!ok) return;
            try {
                const res = await fetch(`/admin/api/helpdesk/agent/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                if (res.ok) { AppToast.success({ message: 'Agente removido com sucesso' }); setTimeout(() => location.reload(), 700); }
                else { const json = await res.json().catch(() => ({ message: 'Erro' })); AppToast.error({ message: json.message || 'Erro ao remover agente' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
        }
    }
}

export function agentForm() {
    return {
        submitting: false,
        form: { name: '', email: '', role: '' },
        errors: {},
        async submit() {
            if (this.submitting) return;
            this.submitting = true;
            this.errors = {};
            try {
                const res = await fetch('/admin/api/helpdesk/agent', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { AppToast.success({ message: data.message || 'Agente criado com sucesso' }); setTimeout(() => location.href = '/admin/tickets-admin/agent', 800); }
                else if (res.status === 422) { this.errors = data.errors || {}; AppToast.error({ message: 'Verifique os campos.' }); }
                else { AppToast.error({ message: data.message || 'Erro ao criar agente' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
            finally { this.submitting = false; }
        }
    }
}

export function agentEditForm(initial) {
    return {
        submitting: false,
        form: { id: initial.id, name: initial.name, email: initial.email, role: initial.role },
        async submit() {
            if (this.submitting) return;
            this.submitting = true;
            try {
                const res = await fetch(`/admin/api/helpdesk/agent/${this.form.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ name: this.form.name, email: this.form.email, role: this.form.role })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { AppToast.success({ message: data.message || 'Agente atualizado com sucesso' }); setTimeout(() => location.href = '/admin/tickets-admin/agent', 800); }
                else { AppToast.error({ message: data.message || 'Erro ao atualizar agente' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
            finally { this.submitting = false; }
        }
    }
}
