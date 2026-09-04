const AGENT_ROLE = '1';
const ADMIN_ROLE = '2';
const CRM_ROLE = '3';
const VALID_ROLES = [AGENT_ROLE, ADMIN_ROLE, CRM_ROLE];

const normalizeRole = (role) => {
    const normalizedRole = String(role ?? '').trim();

    return VALID_ROLES.includes(normalizedRole) ? normalizedRole : '';
};

const normalizeDepartmentId = (departmentId) => {
    const normalizedDepartmentId = String(departmentId ?? '').trim();

    return normalizedDepartmentId === 'null' || normalizedDepartmentId === 'undefined'
        ? ''
        : normalizedDepartmentId;
};

export const resolveUserRole = (userData = {}) => {
    const explicitRole = normalizeRole(userData.role);

    if (explicitRole !== '') {
        return explicitRole;
    }

    if (userData.ticketit_admin == 1) {
        return ADMIN_ROLE;
    }

    if (userData.ticketit_agent == 1) {
        return AGENT_ROLE;
    }

    if (userData.department_id == 3) {
        return CRM_ROLE;
    }

    return '';
};

const getHeaders = () => ({
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
    'Accept': 'application/json'
});

const normalizeToastType = (type = 'success') => {
    const allowedTypes = ['success', 'error', 'warning', 'info'];

    return allowedTypes.includes(type) ? type : 'info';
};

const extractErrorMessage = (payload, fallbackMessage) => {
    if (payload?.errors) {
        return Object.values(payload.errors).flat()[0] || fallbackMessage;
    }

    return payload?.message || fallbackMessage;
};

const notifyToast = (message, type = 'success', options = {}) => {
    const toastType = normalizeToastType(type);
    const method = globalThis.AppToast?.[toastType] || globalThis.AppToast?.show;

    if (typeof method === 'function') {
        method({ message, ...options, type: toastType });
        return;
    }

    globalThis.dispatchEvent(new CustomEvent('app-toast', {
        detail: { message, type: toastType, ...options },
    }));
};

/**
 * Gerenciador Pai (Global)
 */
export const userManager = ({ defaultDepartmentId = null, crmDepartmentId = null } = {}) => ({
    showCreateModal: false,
    showEditModal: false,
    showUserModal: false,
    showDeleteModal: false,
    loadingCreate: false,
    loadingEdit: false,
    loadingDelete: false,
    selectedUser: null,
    deletePreviewData: null,
    selectedTransferAgentId: null,
    targetUserIdToDelete: null,
    defaultDepartmentId: normalizeDepartmentId(defaultDepartmentId),
    crmDepartmentId: normalizeDepartmentId(crmDepartmentId),

    createForm: {
        name: '',
        email: '',
        role: AGENT_ROLE,
        department_id: normalizeDepartmentId(defaultDepartmentId),
        password: '',
        is_oncall: false,
    },

    editForm: {
        id: null,
        name: '',
        email: '',
        role: AGENT_ROLE,
        department_id: '',
        password: '',
        active: true,
        is_oncall: false,
        deployment_admin: false,
        can_manage_implementation: false,
    },

    init() {
        window.addEventListener('edit-user', (e) => this.openEditModal(e.detail));
        window.addEventListener('show-user', (e) => this.openShowModal(e.detail));
        window.addEventListener('open-delete-user', (e) => this.openDeleteModal(e.detail));
    },

    openDeleteModal(detail) {
        this.targetUserIdToDelete = detail.userId;
        this.deletePreviewData = detail.preview;
        this.selectedTransferAgentId = detail.preview?.default_transfer_agent_id || (detail.preview?.eligible_agents?.[0]?.id ?? null);
        this.loadingDelete = false;
        this.showDeleteModal = true;
    },

    async confirmDeleteUser() {
        if (!this.targetUserIdToDelete) return;

        this.loadingDelete = true;
        try {
            const payload = {};
            if (this.selectedTransferAgentId) {
                payload.transfer_to_user_id = this.selectedTransferAgentId;
            }

            const response = await fetch(`/admin/api/v1/users/${this.targetUserIdToDelete}`, {
                method: 'DELETE',
                headers: getHeaders(),
                body: JSON.stringify(payload)
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(data.message || 'Erro ao excluir usuário');
            }

            this.showDeleteModal = false;
            notifyToast(data.message || 'Usuário excluído com sucesso!', 'success', { persist: true });
            setTimeout(() => location.reload(), 700);
        } catch (e) {
            notifyToast(e.message || 'Erro ao excluir usuário', 'error');
        } finally {
            this.loadingDelete = false;
        }
    },

    openCreateModal() {
        this.createForm.name = '';
        this.createForm.email = '';
        this.createForm.password = '';
        this.createForm.role = AGENT_ROLE;
        this.createForm.department_id = this.defaultDepartmentId;
        this.showCreateModal = true;
    },

    openEditModal(detail) {
        const data = detail.formData;

        this.editForm = {
            id: data.id,
            name: data.name,
            email: data.email,
            role: resolveUserRole(data),
            department_id: normalizeDepartmentId(data.department_id),
            password: '',
            ticketit_admin: !!data.ticketit_admin,
            ticketit_agent: !!data.ticketit_agent,
            active: !!data.active,
            is_oncall: !!data.is_oncall,
            deployment_admin: !!data.deployment_admin,
            can_manage_implementation: !!data.can_manage_implementation,
        };

        this.syncRoleSelection('editForm');
        this.showEditModal = true;
    },

    syncRoleSelection(formName) {
        const form = this[formName];

        if (!form) {
            return;
        }

        const normalizedRole = resolveUserRole(form) || normalizeRole(form.role);
        form.role = normalizedRole || AGENT_ROLE;

        if (normalizeDepartmentId(form.department_id) === '') {
            form.department_id = form.role === CRM_ROLE
                ? (this.crmDepartmentId || this.defaultDepartmentId)
                : this.defaultDepartmentId;
        }
    },

    /**
     * CORREÇÃO: Captura os nomes visuais dos selects da linha
     */
    openShowModal(detail) {
        const data = detail.formData;
        const roleText = detail.role_display || 'Usuário';
        const deptText = detail.department_display || '—';

        this.selectedUser = {
            ...data,
            role_display: roleText,
            department_display: deptText
        };
        this.showUserModal = true;
    },

    async submitCreateForm() {
        this.loadingCreate = true;
        try {
            // Omite o campo password quando vazio para acionar o fluxo de reset por e-mail
            const payload = { ...this.createForm };
            if (!payload.password) delete payload.password;

            const response = await fetch('/admin/api/v1/users', {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify(payload)
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(extractErrorMessage(data, 'Erro ao criar usuário'));
            }

            const message = data.message || 'Usuário criado com sucesso!';

            notifyToast(message, 'success', { persist: true });
            setTimeout(() => location.reload(), 700);
        } catch (e) {
            notifyToast(e.message, 'error');
        } finally {
            this.loadingCreate = false;
        }
    },

    async submitEditForm() {
        this.loadingEdit = true;
        try {
            const payload = { ...this.editForm };
            if (!payload.password) delete payload.password;

            const response = await fetch(`/admin/api/v1/users/${this.editForm.id}`, {
                method: 'PUT',
                headers: getHeaders(),
                body: JSON.stringify(payload)
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(extractErrorMessage(data, 'Erro ao salvar'));
            }

            const message = data.message || 'Usuário atualizado com sucesso!';

            notifyToast(message, 'success', { persist: true });
            setTimeout(() => location.reload(), 700);
        } catch (e) {
            notifyToast(e.message, 'error');
        } finally {
            this.loadingEdit = false;
        }
    }
});

/**
 * Gerenciador da Linha (Filho)
 */
export const userRow = (userId, userData = {}) => ({
    loading: false,
    saved: false,
    formData: {
        id: userId,
        name: userData.name ?? '',
        email: userData.email ?? '',
        role: resolveUserRole(userData),
        department_id: normalizeDepartmentId(userData.department_id),
        ticketit_admin: !!userData.ticketit_admin,
        ticketit_agent: !!userData.ticketit_agent,
        active: !!userData.active,
        deployment_admin: !!userData.deployment_admin,
        can_manage_implementation: !!userData.can_manage_implementation,
        obs: userData.obs ?? userData.notes ?? ''
    },

    async resetPassword() {
        if (!confirm(`Deseja enviar um e-mail de redefinição de senha para ${this.formData.name}?`)) {
            return;
        }

        this.loading = true;
        try {
            const response = await fetch(`/admin/api/v1/users/${userId}/password-reset`, {
                method: 'PUT',
                headers: getHeaders()
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || 'Erro ao enviar reset de senha');
            }

            notifyToast(payload.message || 'E-mail de redefinição enviado!', 'success');
        } catch (e) {
            notifyToast(e.message || 'Erro ao enviar reset de senha', 'error');
        } finally {
            this.loading = false;
        }
    },

    async updateUser() {
        this.loading = true;
        try {
            const response = await fetch(`/admin/api/v1/users/${userId}`, {
                method: 'PUT',
                headers: getHeaders(),
                body: JSON.stringify(this.formData)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(extractErrorMessage(data, 'Erro ao salvar'));
            this.saved = true;
            notifyToast(data.message || 'Salvo!', 'success');
            setTimeout(() => this.saved = false, 2000);
        } catch (e) {
            notifyToast(e.message || 'Erro ao salvar', 'error');
        } finally {
            this.loading = false;
        }
    },

    async deleteUser() {
        this.loading = true;
        try {
            const response = await fetch(`/admin/api/v1/users/${userId}/deletion-preview`, {
                method: 'GET',
                headers: getHeaders()
            });

            const preview = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(preview.message || 'Erro ao verificar vínculos do usuário');
            }

            window.dispatchEvent(new CustomEvent('open-delete-user', {
                detail: { userId, preview }
            }));
        } catch (e) {
            notifyToast(e.message || 'Erro ao verificar vínculos do usuário', 'error');
        } finally {
            this.loading = false;
        }
    }
});

if (typeof window !== 'undefined') {
    window.userManager = userManager;
    window.userRow = userRow;
}
