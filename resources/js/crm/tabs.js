// resources/js/crm/tabs.js

export const crmTabs = (config = {}) => ({
    tab: 'feedbacks',
    isLoading: false,
    pendingCount: Number(config.pendingCount ?? 0),
    finishedCount: Number(config.finishedCount ?? 0),

    init() {
        const url = new URL(globalThis.location.href);
        const tabFromQuery = url.searchParams.get('tab');

        if (tabFromQuery === 'feedbacks' || tabFromQuery === 'concluidos') {
            this.tab = tabFromQuery;
            return;
        }

        if (this.pendingCount === 0 && this.finishedCount > 0) {
            this.tab = 'concluidos';
            url.searchParams.set('tab', 'concluidos');
            globalThis.history.replaceState({}, '', url.toString());
        }
    },

    setTab(name) {
        if (this.tab === name) return;

        this.isLoading = true;

        const url = new URL(globalThis.location.href);
        url.searchParams.set('tab', name);
        globalThis.history.replaceState({}, '', url.toString());

        // Fake network delay for better UX feeling when swapping local tabs
        setTimeout(() => {
            this.tab = name;
            this.isLoading = false;
        }, 300);
    },

    isActive(name) {
        return this.tab === name;
    },

    openCreateFeedback(baseUrl) {
        const formSelect = document.getElementById('feedback-forms');
        const formId = formSelect?.value;
        const url = new URL(baseUrl, globalThis.location.origin);

        if (formId) {
            url.searchParams.set('form_id', formId);
        }

        const popup = globalThis.open(url.toString(), '_blank');

        if (popup) {
            popup.focus();
        } else {
            globalThis.location.href = url.toString();
        }
    },
});
