export function configurationManager() {
    return {
        async deleteConfiguration(id) {
            const ok = await window.confirmModal({
                title: 'Remover configuração?',
                message: 'A configuração do helpdesk será removida permanentemente.',
                confirmLabel: 'Remover',
            });
            if (!ok) return;
            try {
                const res = await fetch(`/admin/api/helpdesk/configuration/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                if (res.ok) { AppToast.success({ message: 'Configuração removida com sucesso' }); setTimeout(() => location.reload(), 700); }
                else { const json = await res.json().catch(() => ({ message: 'Erro' })); AppToast.error({ message: json.message || 'Erro ao remover configuração' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
        }
    }
}

export function configurationForm() {
    return {
        submitting: false,
        form: { key: '', value: '', description: '' },
        errors: {},
        async submit() {
            if (this.submitting) return;
            this.submitting = true;
            this.errors = {};
            try {
                const res = await fetch('/admin/api/helpdesk/configuration', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { AppToast.success({ message: data.message || 'Configuração criada com sucesso' }); setTimeout(() => location.href = '/admin/tickets-admin/configuration', 800); }
                else if (res.status === 422) { this.errors = data.errors || {}; AppToast.error({ message: 'Verifique os campos.' }); }
                else { AppToast.error({ message: data.message || 'Erro ao criar configuração' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
            finally { this.submitting = false; }
        }
    }
}

export function configurationEditForm(initial) {
    return {
        submitting: false,
        form: { id: initial.id, key: initial.key, value: initial.value, description: initial.description },
        async submit() {
            if (this.submitting) return;
            this.submitting = true;
            try {
                const res = await fetch(`/admin/api/helpdesk/configuration/${this.form.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ key: this.form.key, value: this.form.value, description: this.form.description })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { AppToast.success({ message: data.message || 'Configuração atualizada com sucesso' }); setTimeout(() => location.href = '/admin/tickets-admin/configuration', 800); }
                else { AppToast.error({ message: data.message || 'Erro ao atualizar configuração' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
            finally { this.submitting = false; }
        }
    }
}
