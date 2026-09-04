export function feedbackManager() {
    const showToast = (message, type = 'success', options = {}) => {
        const method = globalThis.AppToast?.[type] || globalThis.AppToast?.show;

        if (typeof method === 'function') {
            method({ message, ...options, type });
            return;
        }

        globalThis.dispatchEvent(new CustomEvent('app-toast', {
            detail: { message, type, ...options },
        }));
    };

    return {
        modal: {
            open: false,
            isEdit: false,
            submitting: false,
            form: {
                id: null,
                label: '',
                name: '',
                type: '',
                data: '',
                sort_order: 1,
            },
        },

        /**
         * Abre modal com o formulário limpo para criar novo elemento
         */
        openCreateModal() {
            this.modal.isEdit = false;
            this.modal.form = {
                id: null,
                label: '',
                name: '',
                type: '',
                data: '',
                sort_order: 1,
            };
            this.modal.open = true;
        },

        /**
         * Abre modal com dados do elemento para edição
         */
        openEditModal(element) {
            this.modal.isEdit = true;
            this.modal.form = {
                id: element.id,
                label: element.label,
                name: element.name,
                type: element.type,
                data: element.data || '',
                sort_order: element.sort_order,
            };
            this.modal.open = true;
        },

        /**
         * Submete formulário de criar/editar elemento
         */
        async submitForm() {
            this.modal.submitting = true;

            try {
                const formId = new URLSearchParams(window.location.search).get('form_id');
                const endpoint = this.modal.isEdit
                    ? `/admin/api/v1/crm/feedback/element-types/${this.modal.form.id}`
                    : '/admin/api/v1/crm/feedback/element-types';

                const method = this.modal.isEdit ? 'PUT' : 'POST';

                const response = await fetch(endpoint, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        form_id: formId,
                        label: this.modal.form.label,
                        name: this.modal.form.name,
                        type: this.modal.form.type,
                        data: this.modal.form.data,
                        sort_order: this.modal.form.sort_order,
                    }),
                });

                if (response.ok) {
                    showToast(
                        this.modal.isEdit
                            ? 'Elemento atualizado com sucesso!'
                            : 'Elemento criado com sucesso!',
                        'success',
                        { persist: true }
                    );
                    this.modal.open = false;

                    // Reload da página para refletir mudanças
                    setTimeout(() => {
                        window.location.reload();
                    }, 700);
                } else {
                    const error = await response.json();
                    showToast(
                        error.message || 'Erro ao salvar elemento',
                        'error'
                    );
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro ao comunicar com servidor', 'error');
            } finally {
                this.modal.submitting = false;
            }
        },

        /**
         * Deleta um elemento
         */
        async deleteElement(elementId) {
            const ok = await window.confirmModal({
                title: 'Remover elemento?',
                message: 'O elemento será removido do formulário de feedback permanentemente.',
                confirmLabel: 'Remover',
            });
            if (!ok) return;

            try {
                const response = await fetch(
                    `/admin/api/v1/crm/feedback/element-types/${elementId}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                    }
                );

                if (response.ok) {
                    showToast('Elemento removido com sucesso!', 'success', { persist: true });
                    setTimeout(() => {
                        window.location.reload();
                    }, 700);
                } else {
                    const error = await response.json();
                    showToast(
                        error.message || 'Erro ao remover elemento',
                        'error'
                    );
                }
            } catch (error) {
                console.error('Erro:', error);
                showToast('Erro ao comunicar com servidor', 'error');
            }
        },
    };
}
