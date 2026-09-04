const notifyToast = (message, type = 'success', options = {}) => {
    const toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const method = globalThis.AppToast?.[toastType] || globalThis.AppToast?.show;

    if (typeof method === 'function') {
        method({ message, type: toastType, ...options });
        return;
    }

    globalThis.dispatchEvent(new CustomEvent('app-toast', {
        detail: { message, type: toastType, ...options },
    }));
};

/**
 * Componente principal de gerenciamento de categorias.
 */
export function categoryManager() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showViewModal: false,
        selectedCategory: null,
        viewData: {},
        loadingEdit: false,
        expanded: {},
        categoriesData: {},
        currentParentId: null,

        editForm: {
            category_id: null,
            name: '',
            parent_id: '0',
            priority: 'low',
            permalink: '',
            description: '',
            department_id: ''
        },
        openCreateRootModal() {
            this.showCreateModal = true;

            globalThis.dispatchEvent(new CustomEvent('open-category-create-modal', {
                detail: {
                    mode: 'root',
                    parentId: '0',
                    parentPriority: 'low'
                }
            }));
        },

        openCreateSubcategoryModal(parentId = null, parentPriority = 'low') {
            this.showCreateModal = true;
            this.currentParentId = parentId;

            globalThis.dispatchEvent(new CustomEvent('open-category-create-modal', {
                detail: {
                    mode: 'subcategory',
                    parentId: parentId ? String(parentId) : '0',
                    parentPriority: parentPriority || 'low'
                }
            }));
        },

        closeCreateModal() {
            this.showCreateModal = false;
        },

        openViewModal(category) {
            this.selectedCategory = category;
            this.viewData = category;
            this.showViewModal = true;
        },

        closeViewModal() {
            this.showViewModal = false;
            this.selectedCategory = null;
        },

        openEditModal(category) {
            this.editForm = {
                category_id: category.category_id,
                name: category.name ?? '',
                parent_id: String(category.parent_id ?? 0),
                priority: category.priority ?? 'low',
                permalink: category.permalink ?? '',
                description: category.description ?? '',
                department_id: category.department_id ? String(category.department_id) : ''
            };
            this.showEditModal = true;
        },

        closeEditModal() {
            this.showEditModal = false;
        },

        async submitEditForm() {
            if (this.loadingEdit || !this.editForm.category_id) return;

            this.loadingEdit = true;
            try {
                const response = await fetch(`/admin/api/v1/categories/${this.editForm.category_id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.editForm.name,
                        parent_id: this.editForm.parent_id || '0',
                        priority: this.editForm.priority,
                        permalink: this.editForm.permalink,
                        description: this.editForm.description,
                        department_id: this.editForm.department_id === '' ? null : Number(this.editForm.department_id)
                    })
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao atualizar categoria');
                }

                notifyToast(data.message || 'Categoria atualizada com sucesso!', 'success', { persist: true });
                this.closeEditModal();
                setTimeout(() => globalThis.location.reload(), 700);
            } catch (error) {
                notifyToast(error.message, 'error');
            } finally {
                this.loadingEdit = false;
            }
        },

        toggleChildren(id) {
            const wasExpanded = this.expanded[id];
            this.expanded = {};

            if (!wasExpanded) {
                this.expanded[id] = true;
                this.currentParentId = id;
            } else {
                this.currentParentId = null;
            }
        },

        getCategoryName(id) {
            return this.categoriesData[id]?.name || 'Categoria';
        },

        loadCategoriesData() {
            const elements = document.querySelectorAll('[data-category-id]');
            elements.forEach((el) => {
                this.categoriesData[el.dataset.categoryId] = {
                    name: el.dataset.categoryName,
                    priority: el.dataset.categoryPriority || 'low'
                };
            });
        },

        init() {
            this.loadCategoriesData();

            this.$el.addEventListener('modal-close-create', () => {
                this.closeCreateModal();
            });

            this.$el.addEventListener('open-category-view', (event) => {
                this.openViewModal(event.detail);
            });

            this.$el.addEventListener('open-category-edit', (event) => {
                this.openEditModal(event.detail);
            });

            this.$watch('showCreateModal', () => this.syncBodyScroll());
            this.$watch('showEditModal', () => this.syncBodyScroll());
            this.$watch('showViewModal', () => this.syncBodyScroll());
        },

        syncBodyScroll() {
            document.body.style.overflow = (this.showCreateModal || this.showEditModal || this.showViewModal) ? 'hidden' : 'auto';
        }
    };
}

/**
 * Componente para cada linha da tabela.
 */
export function categoryRow(categoryData) {
    return {
        categoryId: categoryData.category_id,
        loading: false,
        saved: false,
        formData: {
            priority: categoryData.priority || 'low',
            permalink: categoryData.description?.permalink || ''
        },

        buildCategoryPayload() {
            return {
                id: this.categoryId,
                category_id: this.categoryId,
                name: categoryData.description?.name || '',
                parent_id: categoryData.parent_id ?? null,
                parent_name: categoryData.parent?.description?.name || 'Categoria raiz',
                priority: this.formData.priority,
                priority_label: {
                    urgent: 'Urgente',
                    high: 'Alta',
                    low: 'Baixa'
                }[this.formData.priority] || 'Baixa',
                permalink: this.formData.permalink || '',
                description: categoryData.description?.description || '',
                department_id: categoryData.department_id ?? '',
            };
        },

        openViewModal() {
            this.$dispatch('open-category-view', this.buildCategoryPayload());
        },

        openEditModal() {
            this.$dispatch('open-category-edit', this.buildCategoryPayload());
        },

        async updateCategory() {
            if (this.loading) return;

            this.loading = true;
            try {
                const response = await fetch(`/admin/api/v1/categories/${this.categoryId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao atualizar categoria');
                }

                this.saved = true;
                notifyToast(data.message || 'Atualizado com sucesso!', 'success');

                setTimeout(() => {
                    this.saved = false;
                }, 2000);
            } catch (error) {
                notifyToast(error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async deleteCategory() {
            if (this.loading) return;
            const ok = await window.confirmModal({
                title: 'Excluir categoria?',
                message: 'A categoria será removida permanentemente. Esta ação não pode ser desfeita.',
                confirmLabel: 'Excluir',
            });
            if (!ok) return;

            this.loading = true;
            try {
                const response = await fetch(`/admin/api/v1/categories/${this.categoryId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao excluir categoria');
                }

                notifyToast(data.message || 'Categoria excluída com sucesso!', 'success', { persist: true });

                setTimeout(() => {
                    globalThis.location.reload();
                }, 700);
            } catch (error) {
                notifyToast(error.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    };
}

/**
 * Componente do formulário de criação (modal).
 */
export function createCategoryForm() {
    return {
        loading: false,
        createMode: 'root',
        formData: {
            name: '',
            parent_id: '0',
            priority: 'low',
            permalink: '',
            description: '',
            department_id: ''
        },
        errors: {},

        init() {
            globalThis.addEventListener('open-category-create-modal', (event) => {
                this.applyCreateContext(event.detail || {});
            });

            this.$watch('formData.parent_id', (value) => {
                if (this.createMode !== 'subcategory') {
                    return;
                }

                if (!value || value === '0' || !this.$refs.parentSelect) {
                    return;
                }

                const selectedOption = this.$refs.parentSelect.options[this.$refs.parentSelect.selectedIndex];
                const parentPriority = selectedOption?.getAttribute('data-priority');
                if (parentPriority) {
                    this.formData.priority = parentPriority;
                }
            });
        },

        get isSubcategoryMode() {
            return this.createMode === 'subcategory';
        },

        get modalTitle() {
            return this.isSubcategoryMode ? 'Nova Subcategoria' : 'Nova Categoria';
        },

        get modeBadgeLabel() {
            return this.isSubcategoryMode ? 'Fluxo de Subcategoria' : 'Fluxo de Categoria';
        },

        get modeDescription() {
            return this.isSubcategoryMode
                ? 'Selecione a categoria e cadastre a subcategoria vinculada.'
                : 'Cadastre uma categoria para a árvore de atendimento.';
        },

        get submitLabel() {
            if (this.loading) {
                return this.isSubcategoryMode ? 'Criando subcategoria...' : 'Criando categoria...';
            }

            return this.isSubcategoryMode ? 'Criar Subcategoria' : 'Criar Categoria';
        },

        get submitEndpoint() {
            return this.isSubcategoryMode
                ? '/admin/api/v1/categories/subcategory'
                : '/admin/api/v1/categories/root';
        },

        applyCreateContext(context) {
            this.createMode = context.mode === 'subcategory' ? 'subcategory' : 'root';
            this.resetForm();

            if (this.createMode === 'subcategory') {
                const parentId = context.parentId && context.parentId !== '0'
                    ? String(context.parentId)
                    : '';
                this.formData.parent_id = parentId;

                if (context.parentPriority) {
                    this.formData.priority = context.parentPriority;
                }
            }
        },

        resetForm() {
            this.loading = false;
            this.errors = {};
            this.formData = {
                name: '',
                parent_id: this.createMode === 'subcategory' ? '' : '0',
                priority: 'low',
                permalink: '',
                description: '',
                department_id: ''
            };
        },

        normalizeErrors(errors) {
            const normalized = {};

            Object.entries(errors || {}).forEach(([key, value]) => {
                normalized[key] = Array.isArray(value) ? value[0] : value;
            });

            return normalized;
        },

        buildPayload() {
            const payload = {
                name: this.formData.name,
                priority: this.formData.priority,
                permalink: this.formData.permalink,
                description: this.formData.description,
                department_id: this.formData.department_id === '' ? null : Number(this.formData.department_id)
            };

            if (this.isSubcategoryMode) {
                payload.parent_id = this.formData.parent_id;
            }

            return payload;
        },

        async submitForm() {
            if (this.loading) return;

            if (this.isSubcategoryMode && (!this.formData.parent_id || this.formData.parent_id === '0')) {
                this.errors = { parent_id: 'Selecione a categoria.' };
                notifyToast('Selecione a categoria da subcategoria.', 'error');
                return;
            }

            this.loading = true;
            this.errors = {};

            try {
                const response = await fetch(this.submitEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.buildPayload())
                });

                const data = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.errors = this.normalizeErrors(data.errors);
                    throw new Error(data.message || 'Verifique os campos do formulário.');
                }

                if (!response.ok) {
                    throw new Error(data.message || 'Erro ao criar categoria.');
                }

                notifyToast(
                    data.message || (this.isSubcategoryMode ? 'Subcategoria criada com sucesso!' : 'Categoria criada com sucesso!'),
                    'success',
                    { persist: true }
                );

                setTimeout(() => {
                    globalThis.location.reload();
                }, 700);
            } catch (error) {
                notifyToast(error.message, 'error');
            } finally {
                this.loading = false;
            }
        }
    };
}
