import '../echo.js';

const STATUS_LABELS = {
    new: 'Nova', pen: 'Pendente', pro: 'Em andamento',
    don: 'Concluída', tdo: 'Concluída (TI)', sto: 'Parada',
    can: 'Cancelada', rej: 'Rejeitada', bin: 'Excluída',
};

const STATUS_TOAST_TYPES = {
    new: 'info',
    pen: 'warning',
    pro: 'info',
    don: 'success',
    tdo: 'success',
    sto: 'warning',
    can: 'warning',
    rej: 'warning',
    bin: 'warning',
};

const notifyToast = (message, type = 'info', options = {}) => {
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

export function taskNotifications(userId) {
    return {
        notifications: [],
        unread: 0,
        open: false,

        init() {
            this.loadNotifications();
            this.subscribeEcho(userId);
        },

        async loadNotifications() {
            try {
                const res = await fetch('/admin/tasks/notifications', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const json = await res.json();
                this.notifications = json.data ?? json;
                this.unread = this.notifications.filter(n => !n.seen).length;
            } catch (_) {}
        },

        subscribeEcho(uid) {
            if (typeof window.Echo === 'undefined') return;

            window.Echo.private(`tasks.user.${uid}`)
                .listen('.task.status.changed', (payload) => {
                    this.notifyStatusToast(payload);
                    this.unread++;
                    this.notifications.unshift({
                        id: null,
                        task_id: payload.task_id,
                        content: `"${payload.task_title}" → ${payload.status_label}`,
                        seen: 0,
                        status: payload.new_status,
                        timestamp: 'agora',
                    });
                });
        },

        notifyStatusToast(payload) {
            const statusLabel = payload.status_label || this.statusLabel(payload.new_status);
            const toastType = STATUS_TOAST_TYPES[payload.new_status] ?? 'info';
            const message = `Tarefa #${payload.task_id} "${payload.task_title}" atualizada para ${statusLabel}.`;

            notifyToast(message, toastType, { duration: 6000 });
        },

        async markSeen(notification) {
            if (!notification.id || notification.seen) return;
            await fetch(`/admin/tasks/notifications/${notification.id}/seen`, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            notification.seen = 1;
            this.unread = Math.max(0, this.unread - 1);
        },

        statusLabel: (s) => STATUS_LABELS[s] ?? s,
    };
}
