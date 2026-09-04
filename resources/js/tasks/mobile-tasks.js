const STATUS_MAP = {
    new: { label: 'Nova',        color: 'bg-indigo-100 text-indigo-700',   dot: 'bg-indigo-500'  },
    pen: { label: 'Pendente',    color: 'bg-amber-100 text-amber-700',     dot: 'bg-amber-500'   },
    pro: { label: 'Em andamento',color: 'bg-blue-100 text-blue-700',       dot: 'bg-blue-500'    },
    sto: { label: 'Parada',      color: 'bg-rose-100 text-rose-700',       dot: 'bg-rose-500'    },
    tdo: { label: 'Conc. (TI)',  color: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-400' },
    don: { label: 'Concluída',   color: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
    can: { label: 'Cancelada',   color: 'bg-gray-100 text-gray-500',       dot: 'bg-gray-400'    },
    rej: { label: 'Rejeitada',   color: 'bg-slate-100 text-slate-500',     dot: 'bg-slate-400'   },
};

const OPEN_STATUSES = new Set(['new', 'pen', 'pro', 'sto']);

const normalizeId = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value).trim();
};

const normalizeSearchText = (value) => String(value ?? '').toLowerCase();

const taskMatchesSearch = (task, rawQuery) => {
    const query = normalizeSearchText(rawQuery).trim();

    if (query === '') {
        return true;
    }

    const taskId = normalizeId(task?.id);

    return [
        taskId,
        taskId ? `#${taskId}` : '',
        task?.title,
        task?.content,
        task?.customer?.trade_name,
        task?.customer?.name,
        task?.project?.name,
        task?.user?.name,
        task?.author?.name,
        task?.tester?.name,
    ].some((field) => normalizeSearchText(field).includes(query));
};

const normalizeModuleList = (moduleTree = []) => {
    if (!Array.isArray(moduleTree)) {
        return [];
    }

    return moduleTree.map((module) => ({
        id: normalizeId(module.id),
        name: module.name ?? 'Módulo',
        scheduleModuleId: normalizeId(module.scheduleModuleId),
        childs: Array.isArray(module.childs)
            ? module.childs.map((child) => ({
                id: normalizeId(child.id),
                name: child.name ?? 'Submódulo',
            }))
            : [],
    }));
};

const normalizeProjectModuleTree = (projectModuleTree = {}) => {
    if (!projectModuleTree || typeof projectModuleTree !== 'object' || Array.isArray(projectModuleTree)) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(projectModuleTree).map(([projectId, modules]) => [
            normalizeId(projectId),
            normalizeModuleList(modules),
        ])
    );
};

const normalizeProjects = (projects = []) => {
    if (!Array.isArray(projects)) {
        return [];
    }

    return projects.map((project) => ({
        id: normalizeId(project.id),
        name: project.name ?? 'Projeto',
    }));
};

const resolveInitialModuleSelection = (moduleTree, oldForm = {}) => {
    const explicitModuleId = normalizeId(oldForm.module_label_id);
    const explicitSubmoduleId = normalizeId(oldForm.submodule_label_id);

    if (explicitModuleId && moduleTree.some((module) => module.id === explicitModuleId)) {
        const matchingSubmodule = explicitSubmoduleId
            && moduleTree
                .find((module) => module.id === explicitModuleId)
                ?.childs.some((child) => child.id === explicitSubmoduleId);

        return {
            moduleId: explicitModuleId,
            submoduleId: matchingSubmodule ? explicitSubmoduleId : '',
        };
    }

    const selectedLabelIds = Array.isArray(oldForm.labels) ? oldForm.labels : [];
    const normalizedSelectedIds = selectedLabelIds.map(normalizeId).filter(Boolean);

    for (const module of moduleTree) {
        if (normalizedSelectedIds.includes(module.id)) {
            return { moduleId: module.id, submoduleId: '' };
        }

        const selectedChild = module.childs.find((child) => normalizedSelectedIds.includes(child.id));

        if (selectedChild) {
            return { moduleId: module.id, submoduleId: selectedChild.id };
        }
    }

    return { moduleId: '', submoduleId: '' };
};

export function mobileTasks(config = {}) {
    const projects = normalizeProjects(config.projects);
    const projectModuleTree = normalizeProjectModuleTree(config.projectModuleTree);
    const oldForm = config.oldForm ?? {};
    const initialProjectId = normalizeId(oldForm.project_id);
    const initialSelection = resolveInitialModuleSelection(
        projectModuleTree[initialProjectId] ?? [],
        oldForm
    );

    return {
        tasks: [],
        filtered: [],
        loading: true,
        search: '',
        taskIdFilter: '',
        statusFilter: 'open',
        classificationFilter: '',
        customerFilter: '',
        projectFilter: '',
        urlSyncTimeout: null,
        expandedTaskId: normalizeId(config.initialExpandedTaskId),
        editingTaskId: normalizeId(config.initialEditingTaskId),
        projects,
        projectModuleTree,
        selectedProjectId: normalizeId(oldForm.project_id),
        selectedModuleId: initialSelection.moduleId,
        selectedSubmoduleId: initialSelection.submoduleId,

        /**
         * Inicializa o componente com dados injetados pelo servidor (Blade @js).
         * Chamado via x-init na view.
         */
        initFromServer(serverTasks, initialFilters = {}) {
            this.tasks                = serverTasks ?? [];
            this.statusFilter         = initialFilters.status || 'open';
            this.search               = initialFilters.search || '';
            this.taskIdFilter         = normalizeId(initialFilters.taskId);
            this.classificationFilter = initialFilters.classification || '';
            this.customerFilter       = normalizeId(initialFilters.customerId);
            this.projectFilter        = normalizeId(initialFilters.projectId);
            this.loading              = false;
            this.syncExpandedTaskFromUrl();
            this.applyFilter();
            this.$watch('search',               () => this.handleFilterStateChange());
            this.$watch('taskIdFilter',         () => this.handleFilterStateChange());
            this.$watch('statusFilter',         () => this.handleFilterStateChange());
            this.$watch('classificationFilter', () => this.handleFilterStateChange());
            this.$watch('customerFilter',       () => this.handleFilterStateChange());
            this.$watch('projectFilter',        () => this.handleFilterStateChange());
        },

        syncExpandedTaskFromUrl() {
            if (typeof window === 'undefined') {
                return;
            }

            const taskId = normalizeId(new URL(window.location.href).searchParams.get('task'));

            if (taskId !== '') {
                this.expandedTaskId = taskId;

                if (this.editingTaskId === '') {
                    this.editingTaskId = null;
                }
            }
        },

        handleFilterStateChange() {
            this.applyFilter();
            this.queueUrlSync();
        },

        hasActiveFilters() {
            return this.statusFilter !== 'open'
                || this.search.trim() !== ''
                || this.taskIdFilter !== ''
                || this.classificationFilter !== ''
                || this.customerFilter !== ''
                || this.projectFilter !== '';
        },

        clearFilters() {
            this.search = '';
            this.taskIdFilter = '';
            this.statusFilter = 'open';
            this.classificationFilter = '';
            this.customerFilter = '';
            this.projectFilter = '';
            this.applyFilter();
            this.syncUrl();
        },

        queueUrlSync() {
            if (typeof window === 'undefined') {
                return;
            }

            if (this.urlSyncTimeout) {
                window.clearTimeout(this.urlSyncTimeout);
            }

            this.urlSyncTimeout = window.setTimeout(() => {
                this.syncUrl();
            }, 120);
        },

        syncUrl() {
            if (typeof window === 'undefined') {
                return;
            }

            const url = new URL(window.location.href);
            const search = this.search.trim();
            const taskId = normalizeId(this.taskIdFilter);
            const classification = this.classificationFilter.trim();
            const customerId = normalizeId(this.customerFilter);
            const projectId = normalizeId(this.projectFilter);

            if (search !== '') {
                url.searchParams.set('q', search);
            } else {
                url.searchParams.delete('q');
            }

            if (taskId !== '') {
                url.searchParams.set('task_id', taskId);
            } else {
                url.searchParams.delete('task_id');
            }

            if (this.statusFilter !== 'open') {
                url.searchParams.set('status', this.statusFilter);
            } else {
                url.searchParams.delete('status');
            }

            if (classification !== '') {
                url.searchParams.set('classification', classification);
            } else {
                url.searchParams.delete('classification');
            }

            if (customerId !== '') {
                url.searchParams.set('customer_id', customerId);
            } else {
                url.searchParams.delete('customer_id');
            }

            if (projectId !== '') {
                url.searchParams.set('project_id', projectId);
            } else {
                url.searchParams.delete('project_id');
            }

            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
        },

        hasTaskModules() {
            return this.availableModules().length > 0;
        },

        availableProjects() {
            return this.projects;
        },

        availableModules() {
            return this.projectModuleTree[this.selectedProjectId] ?? [];
        },

        availableSubmodules() {
            return this.availableModules().find((module) => module.id === this.selectedModuleId)?.childs ?? [];
        },

        selectedModuleHasChildren() {
            return this.availableSubmodules().length > 0;
        },

        handleProjectChange() {
            if (this.selectedProjectId === '') {
                this.selectedModuleId = '';
                this.selectedSubmoduleId = '';
                return;
            }

            if (!this.availableModules().some((module) => module.id === this.selectedModuleId)) {
                this.selectedModuleId = '';
                this.selectedSubmoduleId = '';
                return;
            }

            this.handleModuleChange();
        },

        handleModuleChange() {
            if (!this.availableSubmodules().some((submodule) => submodule.id === this.selectedSubmoduleId)) {
                this.selectedSubmoduleId = '';
            }
        },

        applyFilter() {
            let result = this.tasks;
            const taskId = normalizeId(this.taskIdFilter);
            const classification = this.classificationFilter.trim();
            const customerId = normalizeId(this.customerFilter);
            const projectId = normalizeId(this.projectFilter);

            if (this.statusFilter === 'open') {
                result = result.filter(t => OPEN_STATUSES.has(t.status));
            } else if (this.statusFilter === 'done') {
                result = result.filter(t => !OPEN_STATUSES.has(t.status));
            }

            if (taskId !== '') {
                result = result.filter((task) => normalizeId(task.id) === taskId);
            }

            if (classification !== '') {
                result = result.filter((task) => task.classification === classification);
            }

            if (customerId !== '') {
                result = result.filter((task) => normalizeId(task.customer_id ?? task.customer?.id) === customerId);
            }

            if (projectId !== '') {
                result = result.filter((task) => normalizeId(task.project_id ?? task.project?.id) === projectId);
            }

            if (this.search.trim()) {
                result = result.filter((task) => taskMatchesSearch(task, this.search));
            }

            this.filtered = result;
        },

        statusBadge(status) {
            return STATUS_MAP[status] ?? { label: status, color: 'bg-gray-100 text-gray-500', dot: 'bg-gray-400' };
        },

        formatDate(dateStr) {
            if (!dateStr) return null;
            return new Date(dateStr).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' });
        },

        formatDateTime(dateStr) {
            if (!dateStr) return null;

            return new Date(dateStr).toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        isOverdue(task) {
            return task.delivery_at && OPEN_STATUSES.has(task.status) && new Date(task.delivery_at) < new Date();
        },

        openCount() {
            return this.tasks.filter(t => OPEN_STATUSES.has(t.status)).length;
        },

        excerpt(content, limit = 148) {
            if (!content) {
                return '';
            }

            const normalized = String(content).replace(/\s+/g, ' ').trim();

            if (normalized.length <= limit) {
                return normalized;
            }

            return `${normalized.slice(0, limit).trimEnd()}...`;
        },

        isExpanded(taskId) {
            return normalizeId(taskId) === this.expandedTaskId;
        },

        isEditing(taskId) {
            return normalizeId(taskId) === this.editingTaskId;
        },

        toggleDetails(taskId) {
            const normalizedTaskId = normalizeId(taskId);

            if (this.expandedTaskId === normalizedTaskId) {
                this.expandedTaskId = null;
                this.editingTaskId = null;
                return;
            }

            this.expandedTaskId = normalizedTaskId;
            this.editingTaskId = null;
        },

        openEdit(taskId) {
            const normalizedTaskId = normalizeId(taskId);

            this.expandedTaskId = normalizedTaskId;
            this.editingTaskId = normalizedTaskId;
        },

        closeEdit() {
            this.editingTaskId = null;
        },
    };
}
