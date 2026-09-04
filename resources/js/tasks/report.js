/**
 * Alpine.js component para o relatório de tarefas por módulo (Carlos).
 *
 * Gerencia o dialog de seleção de módulos para impressão.
 * Registrado via: Alpine.data('taskReportPrint', taskReportPrint)
 */
export function taskReportPrint(modules) {
    return {
        modules: [],
        selectedModuleIds: [],
        showPrintDialog: false,
        hasAppliedPrintFilter: false,

        init() {
            this.modules = Array.isArray(modules)
                ? modules.map((module) => ({
                    id: String(module.id),
                    name: module.name,
                    count: Number(module.count || 0),
                }))
                : [];

            this.selectAllModules();
            window.addEventListener('afterprint', () => this.resetPrintFilter());
        },

        openPrintDialog() {
            this.showPrintDialog = true;
            document.body.classList.add('overflow-hidden');
        },

        closePrintDialog() {
            this.showPrintDialog = false;
            document.body.classList.remove('overflow-hidden');
        },

        selectAllModules() {
            this.selectedModuleIds = this.modules.map((module) => module.id);
        },

        clearSelection() {
            this.selectedModuleIds = [];
        },

        applyPrintFilter() {
            const selected = new Set(this.selectedModuleIds.map((id) => String(id)));
            const sections = document.querySelectorAll('[data-report-module-id]');

            sections.forEach((section) => {
                const moduleId = String(section.dataset.reportModuleId || '');
                section.classList.toggle('print-hidden-by-filter', !selected.has(moduleId));
            });

            this.hasAppliedPrintFilter = true;
        },

        resetPrintFilter() {
            if (!this.hasAppliedPrintFilter) return;

            document.querySelectorAll('.print-hidden-by-filter').forEach((section) => {
                section.classList.remove('print-hidden-by-filter');
            });

            this.hasAppliedPrintFilter = false;
            document.body.classList.remove('overflow-hidden');
        },

        printSelectedModules() {
            if (this.selectedModuleIds.length === 0) return;

            this.applyPrintFilter();
            this.closePrintDialog();

            // Aguarda o Alpine remover o dialog do DOM antes de abrir o print
            setTimeout(() => {
                window.print();
            }, 120);
        },
    };
}
