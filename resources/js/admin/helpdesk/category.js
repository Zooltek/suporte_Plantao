export function ticketitCategoryManager() {
    return {
        async deleteCategory(id) {
            const ok = await window.confirmModal({
                title: 'Remover categoria?',
                message: 'A categoria do helpdesk será removida permanentemente.',
                confirmLabel: 'Remover',
            });
            if (!ok) return;
            try {
                const res = await fetch(`/admin/tasks/api/helpdesk/category/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } });
                if (res.ok) { AppToast.success({ message: 'Categoria removida com sucesso' }); setTimeout(() => location.reload(), 700); }
                else { const json = await res.json().catch(() => ({ message: 'Erro' })); AppToast.error({ message: json.message || 'Erro ao remover categoria' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
        }
    }
}

export function ticketitCategoryForm() {
    return {
        submitting: false,
        form: { name: '', color: '#3B82F6' },
        errors: {},
        async submit() {
            if (this.submitting) return;
            this.submitting = true;
            this.errors = {};
            try {
                const res = await fetch('/admin/tasks/api/helpdesk/category', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { AppToast.success({ message: data.message || 'Categoria criada com sucesso' }); setTimeout(() => location.href = '/admin/tasks/tickets-admin/category', 800); }
                else if (res.status === 422) { this.errors = data.errors || {}; AppToast.error({ message: 'Verifique os campos.' }); }
                else { AppToast.error({ message: data.message || 'Erro ao criar categoria' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
            finally { this.submitting = false; }
        }
    }
}

export function ticketitCategoryEditForm(initial) {
    return {
        submitting: false,
        form: { id: initial.id, name: initial.name, color: initial.color || '#3B82F6' },
        async submit() {
            if (this.submitting) return;
            this.submitting = true;
            try {
                const res = await fetch(`/admin/tasks/api/helpdesk/category/${this.form.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ name: this.form.name, color: this.form.color })
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) { AppToast.success({ message: data.message || 'Categoria atualizada com sucesso' }); setTimeout(() => location.href = '/admin/tasks/tickets-admin/category', 800); }
                else { AppToast.error({ message: data.message || 'Erro ao atualizar categoria' }); }
            } catch (e) { console.error(e); AppToast.error({ message: 'Erro ao comunicar com servidor' }); }
            finally { this.submitting = false; }
        }
    }
}
