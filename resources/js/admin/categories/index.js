const notifyToast = (message, type = 'success', options = {}) => {
    const toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const method = window.AppToast?.[toastType] || window.AppToast?.show;

    if (typeof method === 'function') {
        method({ message, type: toastType, ...options });
        return;
    }

    window.dispatchEvent(new CustomEvent('app-toast', {
        detail: { message, type: toastType, ...options },
    }));
};

/**
 * Category Manager - Alpine.js Component para gerenciar categorias
 * Lógica hierárquica: breadcrumbs, accordion, CRUD completo
 */

window.categoryManager = function () {
    return {
        // Estado
        expanded: {},
        categoriesData: {},
        showCreateModal: false,
        showEditModal: false,
        showViewModal: false,
        currentParentId: null,

        // Formulários
        createForm: {
            name: '',
            description: '',
            parent_id: 0,
            priority: 'low',
        },
        editForm: {
            id: null,
            name: '',
            description: '',
            parent_id: 0,
            priority: 'low',
        },
        viewData: {},

        // UI
        loading: false,
        /**
         * Inicialização
         */
        init() {
            this.loadCategoriesData();
        },

        /**
         * Carrega dados das categorias do DOM
         */
        loadCategoriesData() {
            const elements = document.querySelectorAll('[data-category-id]');
            elements.forEach(el => {
                this.categoriesData[el.dataset.categoryId] = {
                    name: el.dataset.categoryName
                };
            });
        },

        /**
         * Accordion: expande/colapsa subcategorias
         */
        toggleChildren(id) {
            const wasExpanded = this.expanded[id];
            // Fecha todos
            this.expanded = {};
            // Abre apenas o clicado se não estava aberto
            if (!wasExpanded) {
                this.expanded[id] = true;
                this.currentParentId = id;
            } else {
                this.currentParentId = null;
            }
        },

        /**
         * Retorna nome da categoria
         */
        getCategoryName(id) {
            return this.categoriesData[id]?.name || 'Categoria';
        },

        /**
         * Verifica se há categoria expandida
         */
        hasExpanded() {
            return Object.keys(this.expanded).some(key => this.expanded[key]);
        },

        /**
         * Abre modal de criação
         */
        openCreateModal() {
            const expandedId = Object.keys(this.expanded).find(key => this.expanded[key]);

            // Reset form
            this.createForm = {
                name: '',
                description: '',
                parent_id: expandedId || 0,
                priority: 'low',
            };

            this.showCreateModal = true;
        },

        /**
         * Abre modal de criação de subcategoria
         */
        openCreateSubcategoryModal(parentId) {
            this.createForm = {
                name: '',
                description: '',
                parent_id: parentId,
                priority: 'low',
            };
            this.showCreateModal = true;
        },

        /**
         * Fecha modal de criação
         */
        closeCreateModal() {
            this.showCreateModal = false;
            this.createForm = { name: '', description: '', parent_id: 0, priority: 'low' };
        },

        /**
         * Submete formulário de criação
         */
        async submitCreateForm() {
            this.loading = true;

            try {
                const response = await fetch('/admin/categories', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.createForm)
                });

                const data = await response.json();

                if (response.ok) {
                    this.showToast('Categoria criada com sucesso!', 'success', { persist: true });
                    this.closeCreateModal();
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Erro ao criar categoria', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showToast('Erro ao criar categoria', 'error');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Abre modal de edição
         */
        openEditModal(category) {
            this.editForm = {
                id: category.id,
                name: category.name || '',
                description: category.description || '',
                parent_id: category.parent_id || 0,
                priority: category.priority || 'low',
            };
            this.showEditModal = true;
        },

        /**
         * Fecha modal de edição
         */
        closeEditModal() {
            this.showEditModal = false;
        },

        /**
         * Submete formulário de edição
         */
        async submitEditForm() {
            this.loading = true;

            try {
                const response = await fetch(`/admin/categories/${this.editForm.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.editForm)
                });

                const data = await response.json();

                if (response.ok) {
                    this.showToast('Categoria atualizada com sucesso!', 'success', { persist: true });
                    this.closeEditModal();
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Erro ao atualizar categoria', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showToast('Erro ao atualizar categoria', 'error');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Abre modal de visualização
         */
        openViewModal(category) {
            this.viewData = category;
            this.showViewModal = true;
        },

        /**
         * Fecha modal de visualização
         */
        closeViewModal() {
            this.showViewModal = false;
        },

        /**
         * Excluir categoria
         */
        async deleteCategory(id, name) {
            const ok = await window.confirmModal({
                title: `Excluir "${name}"?`,
                message: 'A categoria e suas subcategorias serão removidas permanentemente.',
                confirmLabel: 'Excluir',
            });
            if (!ok) return;

            this.loading = true;

            try {
                const response = await fetch(`/admin/categories/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    this.showToast('Categoria excluída com sucesso!', 'success', { persist: true });
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    this.showToast(data.message || 'Erro ao excluir categoria', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showToast('Erro ao excluir categoria', 'error');
            } finally {
                this.loading = false;
            }
        },

        /**
         * Exibe toast notification
         */
        showToast(message, type = 'success', options = {}) {
            notifyToast(message, type, options);
        }
    };
};
