/**
 * adminLayout — componente Alpine para o layout principal do painel admin.
 *
 * Compatível com @alpinejs/csp (sem unsafe-eval).
 * Os dados iniciais do sidebar são lidos via data-sidebar-sections no elemento raiz.
 */
export function adminLayout() {
    return {
        sidebarOpen: false,
        sidebarSections: {},

        init() {
            const raw = this.$el.dataset.sidebarSections;
            this.sidebarSections = raw ? JSON.parse(raw) : {};
        },

        toggleSidebarSection(sectionKey) {
            const isCurrentOpen = Boolean(this.sidebarSections[sectionKey]);
            Object.keys(this.sidebarSections).forEach((key) => {
                this.sidebarSections[key] = false;
            });
            this.sidebarSections[sectionKey] = !isCurrentOpen;
        },

        isSidebarSectionOpen(sectionKey) {
            return Boolean(this.sidebarSections[sectionKey]);
        },

        handleSidebarLinkClick() {
            if (window.innerWidth < 1024) {
                this.sidebarOpen = false;
            }
        },
    };
}
