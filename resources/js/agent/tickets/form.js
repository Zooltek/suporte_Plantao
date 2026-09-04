/**
 * Alpine.js component para o formulário de criação e edição de chamados.
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('ticketForm', ticketForm)
 *
 * Recebe configuração inicial via window.__ticketFormConfig
 * (injetado pela view Blade para evitar data attributes gigantes).
 *
 * Funcionalidades:
 *  - Busca de cliente com filtro em tempo real (nome, CNPJ, cidade, contato)
 *  - Carregamento dinâmico de subcategorias via fetch
 *  - Badge de prioridade da categoria selecionada
 *  - Histórico de chamados da empresa via fetch
 *  - Upload e preview de múltiplos arquivos com drag zone
 *  - Remoção de anexos existentes
 *  - Timer de tempo decorrido (criação de novo chamado)
 */
export function ticketForm(config = globalThis.window?.__ticketFormConfig || {}) {
    const cfg = config;

    return {
        // ── Config vinda do backend (via window) ──────────────────────────
        ticketId: cfg.ticketId || '',
        csrf: cfg.csrf || '',
        urlHistory: cfg.urlHistory || '',
        urlCategory: cfg.urlCategory || '',
        urlCompanySearch: cfg.urlCompanySearch || '',
        companies: cfg.companies || [],
        technicalContexts: cfg.technicalContexts || {},
        versionCatalog: Array.isArray(cfg.versionCatalog) ? cfg.versionCatalog : [],
        availableRatModules: Array.isArray(cfg.availableRatModules) ? cfg.availableRatModules : [],
        departments: Array.isArray(cfg.departments) ? cfg.departments : [],
        isEdit: cfg.isEdit || false,

        // ── Estado de seleção ─────────────────────────────────────────────
        selectedCompany: String(cfg.selectedCompany || ''),
        selectedCategory: String(cfg.selectedCategory || ''),
        selectedSubCategory: String(cfg.selectedSubCategory || ''),
        selectedStatus:      String(cfg.selectedStatus || '4'),
        requiresScheduleIds: (cfg.requiresScheduleIds || []).map(String),
        requiresAgentIds: (cfg.requiresAgentIds || []).map(String),
        selectedAgent: String(cfg.selectedAgent || ''),
        selectedDepartment: String(cfg.selectedDepartment || ''),
        selectedModule: String(cfg.selectedModule || ''),
        selectedRatModule: String(cfg.selectedRatModule || ''),
        versionInput: String(cfg.initialVersion || ''),
        releaseInput: String(cfg.initialRelease || ''),
        suppressCompanyWatch: false,

        // ── Filtro de clientes ────────────────────────────────────────────
        searchClient: '',
        showInactiveClients: false,
        isLoadingRemoteCompanies: false,
        isLoadingSub: false,

        // ── Computed: empresas filtradas ──────────────────────────────────
        get filteredCompanies() {
            let list = this.showInactiveClients
                ? this.companies
                : this.companies.filter(c => c.is_active);

            const term = this.normalizeString(this.searchClient);
            if (!term) return list;

            const nums = term.replace(/\D/g, '');
            return list.filter(c =>
                (c.trade_name && this.normalizeString(c.trade_name).includes(term)) ||
                (c.name && this.normalizeString(c.name).includes(term)) ||
                (c.city && this.normalizeString(c.city).includes(term)) ||
                (c.contact_name && this.normalizeString(c.contact_name).includes(term)) ||
                (c.phone && this.normalizeString(c.phone).includes(term)) ||
                (c.state_abbr && this.normalizeString(c.state_abbr).includes(term)) ||
                (nums.length > 0 && c.cnpj && c.cnpj.replace(/\D/g, '').includes(nums))
            );
        },

        normalizeString(str) {
            if (!str) return '';
            return str.toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        },

        // Busca empresas via API (busca no servidor)
        async filterCompaniesRemote() {
            if (this.searchClient.length < 2) {
                return;
            }

            this.isLoadingRemoteCompanies = true;
            try {
                const response = await fetch(
                    `${this.urlCompanySearch}?q=${encodeURIComponent(this.searchClient)}`,
                    { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
                );
                if (response.ok) {
                    const data = await response.json();
                    const existingIds = new Set(this.companies.map(c => String(c.id)));
                    data.forEach(newCompany => {
                        if (!existingIds.has(String(newCompany.id))) {
                            this.companies.push(newCompany);
                        }
                    });
                }
            } catch (e) {
                console.error('Erro ao buscar empresas', e);
            } finally {
                this.isLoadingRemoteCompanies = false;
            }
        },

        // ── EasyWiki: artigos da categoria ────────────────────────────────
        wikiArticles: [],
        isLoadingWiki: false,
        wikiExpanded: null, // id do artigo expandido

        // ── RAT: checklist dinâmico por módulo ────────────────────────────
        ratGroups: [],        // [{ name, items: [{id, name, label, type}] }]
        ratResponses: {},     // { [item_id]: value }
        isLoadingRat: false,
        ratFeedbackState: 'idle',
        ratFeedbackMessage: '',

        // ── Histórico de atendimentos ─────────────────────────────────────
        historyHtml: '',

        // ── Arquivos (upload) ─────────────────────────────────────────────
        isDragging: false,
        selectedFiles: [], // [{ file: File, previewUrl: string|null, fileType: string }]

        selectedCompanyObj() {
            return this.companies.find(c => String(c.id) === String(this.selectedCompany));
        },

        selectedCompanyModules() {
            return this.selectedCompanyObj()?.module_types ?? [];
        },

        selectedModuleMeta() {
            return this.selectedCompanyModules().find(m => String(m.id) === String(this.selectedModule));
        },

        selectedCompanyRatModules() {
            const companyRatModules = this.selectedCompanyObj()?.schedule_rat_modules ?? [];

            return companyRatModules.length > 0 ? companyRatModules : this.availableRatModules;
        },

        selectedModuleHasLinkedRatTemplate() {
            return Boolean(this.selectedModuleMeta()?.rat_template_id);
        },

        selectedModuleRatTemplateName() {
            const module = this.selectedModuleMeta();

            if (!module?.rat_template_name) {
                return '';
            }

            return module.rat_template_project
                ? `${module.rat_template_name} (${module.rat_template_project})`
                : module.rat_template_name;
        },

        selectedRatModuleName() {
            const selectedRatModule = this.selectedCompanyRatModules()
                .find(rat => String(rat.id) === String(this.selectedRatModule));

            if (selectedRatModule) {
                return this.formatRatModuleDisplay(selectedRatModule);
            }

            return this.selectedModuleRatTemplateName();
        },

        requiresRatTemplateSelection() {
            return Boolean(this.selectedModule) && !this.selectedModuleHasLinkedRatTemplate();
        },

        showRatTemplateSelector() {
            return this.requiresRatTemplateSelection() && this.selectedCompanyRatModules().length > 0;
        },

        formatRatModuleDisplay(ratModule) {
            return ratModule?.project
                ? `${ratModule.name} (${ratModule.project})`
                : ratModule?.name || '';
        },

        formatRatModuleOption(ratModule) {
            const baseLabel = this.formatRatModuleDisplay(ratModule);

            return ratModule?.element_count
                ? `${baseLabel} - ${ratModule.element_count} itens`
                : baseLabel;
        },

        shouldShowRatFeedback() {
            return Boolean(this.selectedModule) && this.ratFeedbackState !== 'idle';
        },

        ratFeedbackTone() {
            return {
                select_template: 'border-sky-200 bg-sky-50/80',
                unavailable: 'border-rose-200 bg-rose-50/80',
                empty_template: 'border-amber-200 bg-amber-50/80',
                error: 'border-rose-200 bg-rose-50/80',
            }[this.ratFeedbackState] || 'border-gray-200 bg-gray-50';
        },

        ratFeedbackIconTone() {
            return {
                select_template: 'text-sky-600',
                unavailable: 'text-rose-600',
                empty_template: 'text-amber-600',
                error: 'text-rose-600',
            }[this.ratFeedbackState] || 'text-gray-500';
        },

        ratFeedbackTextTone() {
            return {
                select_template: 'text-sky-800',
                unavailable: 'text-rose-800',
                empty_template: 'text-amber-800',
                error: 'text-rose-800',
            }[this.ratFeedbackState] || 'text-gray-700';
        },

        ratFeedbackSubtextTone() {
            return {
                select_template: 'text-sky-600',
                unavailable: 'text-rose-600',
                empty_template: 'text-amber-600',
                error: 'text-rose-600',
            }[this.ratFeedbackState] || 'text-gray-500';
        },

        currentTechnicalContext() {
            return this.technicalContexts[String(this.selectedCompany)] ?? {
                software_name: '',
                software_version: '',
                suggested_version: '',
                suggested_release: '',
                has_suggestion: false,
                latest_context_at: null,
            };
        },

        availableVersionOptions() {
            const context = this.currentTechnicalContext();
            const options = [
                ...this.versionCatalog,
                context.software_version || '',
                context.suggested_version || '',
                this.versionInput || '',
            ].filter(Boolean);

            return [...new Set(options)].sort((left, right) => left.localeCompare(right, undefined, { numeric: true }));
        },

        hasTechnicalSuggestion() {
            const context = this.currentTechnicalContext();

            return Boolean(context.has_suggestion || context.suggested_version || context.suggested_release);
        },

        hasTechnicalHistory() {
            const context = this.currentTechnicalContext();

            return Boolean(context.suggested_version || context.suggested_release);
        },

        resolvedTechnicalContextForCompany(companyId = this.selectedCompany) {
            const context = this.technicalContexts[String(companyId)] ?? {
                software_version: '',
                suggested_version: '',
                suggested_release: '',
            };

            if (context.suggested_version || context.suggested_release) {
                return {
                    version: context.suggested_version || '',
                    release: context.suggested_release || '',
                    source: 'history',
                };
            }

            if (context.software_version) {
                return {
                    version: context.software_version || '',
                    release: '',
                    source: 'software',
                };
            }

            return {
                version: '',
                release: '',
                source: 'empty',
            };
        },

        hasRestorableTechnicalContext() {
            if (!this.selectedCompany) {
                return false;
            }

            const context = this.resolvedTechnicalContextForCompany();

            if (context.source === 'empty') {
                return false;
            }

            return this.versionInput !== context.version || this.releaseInput !== context.release;
        },

        isUsingResolvedTechnicalContext() {
            if (!this.selectedCompany) {
                return false;
            }

            const context = this.resolvedTechnicalContextForCompany();

            if (context.source === 'empty') {
                return false;
            }

            return this.versionInput === context.version && this.releaseInput === context.release;
        },

        technicalContextRestoreLabel() {
            return this.resolvedTechnicalContextForCompany().source === 'software'
                ? 'Usar versão do software'
                : 'Restaurar último salvo';
        },

        technicalContextValue(field, fallback = '') {
            const value = this.currentTechnicalContext()?.[field];

            return value ? value : fallback;
        },

        isFinancialBlocked() {
            return !this.isEdit && Boolean(this.selectedCompanyObj()?.financial_irregular);
        },

        // ── Regras de validação dinâmica ──────────────────────────────────
        isResolved() {
            return this.selectedStatus == 3; // 3 = Resolvido/Fechado
        },

        // ── Inicialização ─────────────────────────────────────────────────
        init() {
            if (this.selectedCategory) {
                this.updateCategoryPriority(this.selectedCategory);
                this.loadSubCategories(this.selectedCategory, this.selectedSubCategory);
            }
            if (this.selectedSubCategory) {
                this.loadWikiArticles(this.selectedSubCategory);
            }
            if (this.selectedCompany) {
                const comp = this.companies.find(c => c.id == this.selectedCompany);
                if (comp && !comp.is_active) this.showInactiveClients = true;
                this.loadHistory(this.selectedCompany);

                if (!this.versionInput && !this.releaseInput) {
                    this.syncTechnicalContextForCompany(this.selectedCompany);
                }
            }

            this.$watch('selectedCompany', (value, oldValue) => {
                this.handleSelectedCompanyChange(value, oldValue);
            });

            this.$watch('selectedModule', () => {
                this.handleSelectedModuleChange();
            });

            this.$watch('selectedSubCategory', (val) => {
                this.loadWikiArticles(val);
            });

            // Carrega RAT na edição se já houver módulo selecionado
            if (this.selectedModule) {
                const savedResponses = cfg.ratResponses || {};
                this.handleSelectedModuleChange().then(() => {
                    this.ratResponses = savedResponses;
                });
            }

            // O select de empresa usa x-for para renderizar as opções dinamicamente.
            // O x-model tenta sincronizar antes do x-for concluir de popular o DOM,
            // resultando em nenhuma opção selecionada visualmente ao editar.
            // Força re-sincronização após o x-for concluir a renderização inicial.
            if (this.selectedCompany) {
                const saved = this.selectedCompany;
                this.$nextTick(() => {
                    this.suppressCompanyWatch = true;
                    this.selectedCompany = '';
                    this.$nextTick(() => {
                        this.selectedCompany = saved;
                        this.$nextTick(() => { this.suppressCompanyWatch = false; });
                    });
                });
            }

            this.$watch('selectedStatus', (val) => {
                window.dispatchEvent(new CustomEvent('ticket-status-changed', { detail: { status: val } }));
            });

            // Regras de status baseadas no catálogo canônico.
            // Ao remover o agente, qualquer status que exija responsável volta
            // visualmente para Pendente, mantendo a UX alinhada ao backend.
            this.$watch('selectedAgent', (val) => {
                if (!val && this.requiresAgentIds.includes(this.selectedStatus)) {
                    this.selectedStatus = '4';
                }
            });
        },

        // Mantém o setor coerente com o agente escolhido — o backend já tem
        // essa precedência, mas refletir no formulário evita confusão visual.
        handleAgentChange() {
            const agentId = String(this.selectedAgent || '');
            if (!agentId) {
                return;
            }
            const option = this.$root.querySelector(`#agent_id option[value="${agentId}"]`);
            const deptId = option?.getAttribute('data-department-id') || '';
            if (deptId) {
                this.selectedDepartment = String(deptId);
            }
        },

        handleDepartmentChange() {
            // Marker explícito para permitir hooks futuros (analytics, validação).
            // Nenhum efeito colateral no agente — a escolha manual permanece.
        },

        resolvedDepartmentName() {
            const id = String(this.selectedDepartment || '');
            if (!id) {
                return '';
            }
            const dept = this.departments.find(d => String(d.id) === id);
            return dept?.name || '';
        },

        // ── Handlers de categoria ─────────────────────────────────────────
        categoryChanged(val) {
            this.updateCategoryPriority(val);
            this.loadSubCategories(val);
        },

        updateCategoryPriority(categoryId) {
            if (!categoryId) { this.selectedCategoryPriority = ''; return; }
            const sel = this.$root.querySelector('#category_id');
            const opt = sel?.querySelector(`option[value="${categoryId}"]`);
            this.selectedCategoryPriority = opt ? (opt.getAttribute('data-priority') || '') : '';
        },

        getPriorityLabel(priority) {
            return { low: 'Baixa Prioridade', high: 'Alta Prioridade', urgent: 'Nível Crítico' }[priority] || '';
        },

        getPriorityColor(priority) {
            const map = {
                low: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                high: 'bg-amber-100 text-amber-800 border border-amber-200',
                urgent: 'bg-rose-100 text-rose-800 border border-rose-200 shadow-[0_0_10px_rgba(225,29,72,0.3)]',
            };
            return map[priority] || 'bg-gray-100 text-gray-600 border border-gray-200';
        },

        handleSelectedCompanyChange(companyId, previousCompanyId = '') {
            if (this.suppressCompanyWatch) {
                return;
            }

            const currentCompanyId = String(companyId || '');

            this.selectedModule = '';
            this.selectedRatModule = '';
            this.resetRatChecklistState();

            if (!currentCompanyId) {
                this.versionInput = '';
                this.releaseInput = '';
                this.loadHistory('');

                return;
            }

            this.syncTechnicalContextForCompany(currentCompanyId);
            this.loadHistory(currentCompanyId);
        },

        async handleSelectedModuleChange() {
            this.resetRatChecklistState(false);

            const selectedModule = this.selectedModuleMeta();

            if (!selectedModule) {
                this.selectedRatModule = '';

                return;
            }

            if (selectedModule.rat_template_id) {
                this.selectedRatModule = String(selectedModule.rat_template_id);
                await this.loadRatItems(this.selectedRatModule);

                return;
            }

            const availableRatModules = this.selectedCompanyRatModules();
            const currentRatModuleId = String(this.selectedRatModule || '');

            if (currentRatModuleId && availableRatModules.some(rat => String(rat.id) === currentRatModuleId)) {
                await this.loadRatItems(currentRatModuleId);

                return;
            }

            if (availableRatModules.length === 1) {
                this.selectedRatModule = String(availableRatModules[0].id);
                await this.loadRatItems(this.selectedRatModule);

                return;
            }

            this.selectedRatModule = '';

            if (availableRatModules.length === 0) {
                this.setRatFeedback('unavailable', 'Nenhum checklist RAT técnico está disponível para este cliente.');

                return;
            }

            this.setRatFeedback('select_template', 'Selecione o checklist RAT técnico para carregar as opções deste atendimento.');
        },

        async handleSelectedRatTemplateChange(ratModuleId) {
            this.selectedRatModule = String(ratModuleId || '');

            this.resetRatChecklistState(false);

            if (!this.selectedRatModule) {
                if (this.selectedCompanyRatModules().length === 0) {
                    this.setRatFeedback('unavailable', 'Nenhum checklist RAT técnico está disponível para este cliente.');
                } else {
                    this.setRatFeedback('select_template', 'Selecione o checklist RAT técnico para carregar as opções deste atendimento.');
                }

                return;
            }

            await this.loadRatItems(this.selectedRatModule);
        },

        resetRatChecklistState(clearSelectedRatModule = false) {
            this.ratGroups = [];
            this.ratResponses = {};
            this.isLoadingRat = false;
            this.clearRatFeedback();

            if (clearSelectedRatModule) {
                this.selectedRatModule = '';
            }
        },

        clearRatFeedback() {
            this.ratFeedbackState = 'idle';
            this.ratFeedbackMessage = '';
        },

        setRatFeedback(state, message) {
            this.ratFeedbackState = state;
            this.ratFeedbackMessage = message;
        },

        syncTechnicalContextForCompany(companyId = this.selectedCompany) {
            const context = this.resolvedTechnicalContextForCompany(companyId);

            this.versionInput = context.version;
            this.releaseInput = context.release;
        },

        restoreTechnicalContext() {
            if (!this.selectedCompany) {
                return;
            }

            this.syncTechnicalContextForCompany(this.selectedCompany);
        },

        // ── Fetch: histórico da empresa ───────────────────────────────────
        async loadHistory(id) {
            if (!id) { this.historyHtml = ''; return; }
            let url = `${this.urlHistory}/${id}/history`;
            if (this.ticketId) url += `?id=${this.ticketId}`;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.historyHtml = res.ok
                    ? await res.text()
                    : `<div class="p-4 text-red-500 bg-red-50 rounded-xl">Falha ao carregar histórico: ${res.statusText}</div>`;
            } catch {
                this.historyHtml = '<div class="p-4 text-red-500 bg-red-50 rounded-xl">Falha de rede ao consultar histórico.</div>';
            }
        },

        // ── Fetch: subcategorias ──────────────────────────────────────────
        async loadSubCategories(categoryId, preSelectId = null) {
            this.subCategories = [];
            this.selectedSubCategory = '';
            if (!categoryId) return;

            this.isLoadingSub = true;
            try {
                const res = await fetch(`${this.urlCategory}/${categoryId}/children`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    this.subCategories = await res.json();
                    if (preSelectId) setTimeout(() => this.selectedSubCategory = String(preSelectId), 50);
                }
            } catch (e) {
                console.error('Erro ao carregar subcategorias', e);
            } finally {
                this.isLoadingSub = false;
            }
        },

        // ── Fetch: artigos EasyWiki da categoria ──────────────────────────
        async loadWikiArticles(categoryId) {
            this.wikiArticles = [];
            this.wikiExpanded = null;
            if (!categoryId) return;

            this.isLoadingWiki = true;
            try {
                const res = await fetch(`${this.urlCategory}/${categoryId}/articles`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (res.ok) {
                    this.wikiArticles = await res.json();
                }
            } catch (e) {
                console.error('Erro ao carregar artigos da EasyWiki', e);
            } finally {
                this.isLoadingWiki = false;
            }
        },

        toggleWikiArticle(id) {
            this.wikiExpanded = this.wikiExpanded === id ? null : id;
        },

        // ── Upload de arquivos ────────────────────────────────────────────
        _classifyFile(file) {
            const mime = file.type.toLowerCase();
            const ext  = file.name.split('.').pop().toLowerCase();
            if (mime.startsWith('image/'))                                                                     return 'image';
            if (mime === 'application/pdf')                                                                    return 'pdf';
            if (mime.startsWith('video/'))                                                                     return 'video';
            if (['xls','xlsx','csv'].includes(ext) || mime.includes('spreadsheet') || mime.includes('excel')) return 'spreadsheet';
            if (['doc','docx'].includes(ext) || mime.includes('word') || mime.includes('document'))           return 'word';
            if (['zip','rar','7z','tar','gz'].includes(ext))                                                   return 'archive';
            return 'generic';
        },

        _wrapFile(file) {
            const fileType   = this._classifyFile(file);
            const previewUrl = (fileType === 'image' || fileType === 'pdf')
                ? URL.createObjectURL(file)
                : null;
            return { file, previewUrl, fileType };
        },

        handleFileSelect(event) {
            const wrapped = Array.from(event.target.files).map(f => this._wrapFile(f));
            this.selectedFiles = [...this.selectedFiles, ...wrapped];
            event.target.value = ''; // reset para permitir re-seleção do mesmo arquivo
            this.syncFileInput();
        },

        handleDrop(event) {
            this.isDragging = false;
            const wrapped = Array.from(event.dataTransfer.files).map(f => this._wrapFile(f));
            this.selectedFiles = [...this.selectedFiles, ...wrapped];
            this.syncFileInput();
        },

        removeFile(index) {
            const item = this.selectedFiles[index];
            if (item?.previewUrl) URL.revokeObjectURL(item.previewUrl);
            this.selectedFiles.splice(index, 1);
            this.syncFileInput();
        },

        syncFileInput() {
            const dt = new DataTransfer();
            this.selectedFiles.forEach(item => dt.items.add(item.file));
            if (this.$refs.fileInput) this.$refs.fileInput.files = dt.files;
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        },

        // ── Remoção de anexo existente ────────────────────────────────────
        removeAttachment(id) {
            const el = document.getElementById('attachment-' + id);
            if (el) el.remove();
        },

        // ── RAT: carrega itens de checklist por módulo ────────────────────
        async loadRatItems(moduleId) {
            this.ratGroups = [];
            this.ratResponses = {};

            if (!moduleId) {
                return;
            }

            this.isLoadingRat = true;
            this.clearRatFeedback();

            try {
                const url = new URL(cfg.urlRatElements || '', globalThis.window?.location?.origin || 'http://localhost');
                url.searchParams.set('module_id', moduleId);
                url.searchParams.set('module_context', 'schedule');

                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!res.ok) {
                    this.setRatFeedback('error', 'Falha ao carregar o checklist RAT para o template selecionado.');

                    return;
                }

                const payload = await res.json();

                if (Array.isArray(payload)) {
                    const grouped = {};
                    payload.forEach(item => {
                        const groupName = item.group_name || 'Geral';
                        if (!grouped[groupName]) grouped[groupName] = { name: groupName, items: [] };
                        grouped[groupName].items.push(item);
                    });
                    this.ratGroups = Object.values(grouped);
                } else {
                    this.ratGroups = Object.entries(payload || {}).map(([groupName, items]) => ({
                        name: groupName,
                        items: Array.isArray(items) ? items : [],
                    }));
                }

                if (this.ratGroups.length === 0) {
                    this.setRatFeedback(
                        'empty_template',
                        `O template ${this.selectedRatModuleName() ? `"${this.selectedRatModuleName()}" ` : ''}não possui itens de checklist cadastrados.`
                    );
                }
            } catch (e) {
                console.error('Erro ao carregar checklist RAT', e);
                this.setRatFeedback('error', 'Falha ao carregar o checklist RAT para o template selecionado.');
            } finally {
                this.isLoadingRat = false;
            }
        },

        ratHasItems() {
            return this.ratGroups.some(g => g.items.length > 0);
        },
    };
}