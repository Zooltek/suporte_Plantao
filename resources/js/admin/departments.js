import Alpine from 'alpinejs';

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

/**
 * Gerenciador de Departamentos (Alpine.js)
 */
export function departmentManager() {
    return {
        showCreateModal: false,
        showEditModal: false,
        loadingCreate: false,
        loadingEdit: false,

        createForm: {
            name: '',
            description: ''
        },

        editForm: {
            id: null,
            name: '',
            description: ''
        },

        expanded: {},

        toggle(id) {
            this.expanded[id] = !this.expanded[id];
        },

        init() {
        },

        openCreateModal() {
            this.createForm = { name: '', description: '' };
            this.showCreateModal = true;
        },

        openEditModal(dept) {
            this.editForm = {
                id: dept.id,
                name: dept.name,
                description: dept.description || ''
            };
            this.showEditModal = true;
        },

        async submitCreateForm() {
            if (this.loadingCreate || !this.createForm.name.trim()) {
                return;
            }

            this.loadingCreate = true;

            try {
                const response = await fetch('/admin/api/v1/departments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.createForm)
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao criar departamento');
                }

                showToast('Departamento criado com sucesso!', 'success', { persist: true });
                this.showCreateModal = false;

                // Recarregar página após delay
                setTimeout(() => location.reload(), 700);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.loadingCreate = false;
            }
        },

        async submitEditForm() {
            if (this.loadingEdit || !this.editForm.id || !this.editForm.name.trim()) {
                return;
            }

            this.loadingEdit = true;

            try {
                const response = await fetch(`/admin/api/v1/departments/${this.editForm.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.editForm.name,
                        description: this.editForm.description
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao atualizar departamento');
                }

                showToast('Departamento atualizado com sucesso!', 'success', { persist: true });
                this.showEditModal = false;

                // Recarregar página após delay
                setTimeout(() => location.reload(), 700);
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.loadingEdit = false;
            }
        },

        async deleteDepartment(deptId, deptName) {
            const ok = await window.confirmModal({
                title: `Excluir "${deptName}"?`,
                message: 'O departamento será removido permanentemente. Esta ação não pode ser desfeita.',
                confirmLabel: 'Excluir',
            });
            if (!ok) return;

            try {
                const response = await fetch(`/admin/api/v1/departments/${deptId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.message || 'Erro ao excluir departamento');
                }

                showToast('Departamento excluído com sucesso!', 'success', { persist: true });

                // Recarregar página após delay
                setTimeout(() => location.reload(), 700);
            } catch (error) {
                showToast(error.message, 'error');
            }
        }
    };
}

// Registrar no Alpine globalmente
if (typeof window.Alpine !== 'undefined') {
    window.departmentManager = departmentManager;
} else {
    window.departmentManager = departmentManager;
    document.addEventListener('alpine:init', () => {
        window.departmentManager = departmentManager;
    });
}
