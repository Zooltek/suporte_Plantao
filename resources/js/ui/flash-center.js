const FLASH_STORAGE_KEY = 'app-flash-toasts';

const TOAST_DURATIONS = {
    success: 5000,
    error: 7000,
    warning: 6000,
    info: 5000,
};

const normalizeType = (type = 'info') => {
    const allowedTypes = ['success', 'error', 'warning', 'info'];

    return allowedTypes.includes(type) ? type : 'info';
};

const normalizeMessage = (message = '') => {
    if (typeof message === 'string') {
        return message.trim();
    }

    if (message == null) {
        return '';
    }

    return String(message).trim();
};

const resolveMessage = (options = {}) => normalizeMessage(options.message || options.title || '');

const normalizeDuration = (type, duration) => {
    const parsedDuration = Number(duration);

    if (Number.isFinite(parsedDuration) && parsedDuration > 0) {
        return parsedDuration;
    }

    return TOAST_DURATIONS[type] ?? TOAST_DURATIONS.info;
};

const normalizeToast = (options = {}) => {
    const type = normalizeType(options.type);
    const message = resolveMessage(options);

    return {
        id: options.id || `toast-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        type,
        message,
        duration: normalizeDuration(type, options.duration ?? options.timeout),
    };
};

const persistToast = (toast) => {
    if (!toast.message || typeof sessionStorage === 'undefined') {
        return;
    }

    try {
        const storedToasts = JSON.parse(sessionStorage.getItem(FLASH_STORAGE_KEY) || '[]');

        storedToasts.push({
            message: toast.message,
            type: toast.type,
            duration: toast.duration,
        });

        sessionStorage.setItem(FLASH_STORAGE_KEY, JSON.stringify(storedToasts));
    } catch {
        // Ignora indisponibilidade de sessionStorage sem interromper a interface.
    }
};

const consumePersistedToasts = () => {
    if (typeof sessionStorage === 'undefined') {
        return [];
    }

    try {
        const storedToasts = JSON.parse(sessionStorage.getItem(FLASH_STORAGE_KEY) || '[]');
        sessionStorage.removeItem(FLASH_STORAGE_KEY);

        return Array.isArray(storedToasts)
            ? storedToasts.map((toast) => normalizeToast(toast)).filter((toast) => toast.message)
            : [];
    } catch {
        return [];
    }
};

export const emitToast = (options = {}) => {
    const toast = normalizeToast(options);

    if (!toast.message) {
        return null;
    }

    if (options.persist) {
        persistToast(toast);
    }

    globalThis.dispatchEvent(new CustomEvent('app-toast', {
        detail: toast,
    }));

    return toast;
};

const createToastMethod = (type) => (options = {}) => emitToast({
    ...options,
    type,
});

export const flashCenter = (initialToasts = []) => ({
    toasts: [],
    eventHandler: null,

    init() {
        this.eventHandler = (event) => this.pushToast(event.detail || {});

        globalThis.addEventListener('app-toast', this.eventHandler);
        globalThis.addEventListener('show-toast', this.eventHandler);

        [
            ...consumePersistedToasts(),
            ...(Array.isArray(initialToasts) ? initialToasts : []),
        ].forEach((toast) => this.pushToast(toast));
    },

    pushToast(options = {}) {
        const toast = normalizeToast(options);

        if (!toast.message) {
            return;
        }

        this.toasts.push({
            ...toast,
            visible: true,
            timeoutId: window.setTimeout(() => this.dismissToast(toast.id), toast.duration),
        });
    },

    dismissToast(id) {
        const toast = this.toasts.find((item) => item.id === id);

        if (!toast || !toast.visible) {
            return;
        }

        if (toast.timeoutId) {
            window.clearTimeout(toast.timeoutId);
            toast.timeoutId = null;
        }

        toast.visible = false;

        window.setTimeout(() => {
            this.toasts = this.toasts.filter((item) => item.id !== id);
        }, 220);
    },

    themeFor(type = 'info') {
        const themes = {
            success: {
                panel: 'border-emerald-200 bg-emerald-50/95',
                iconWrap: 'bg-emerald-100 text-emerald-600',
                message: 'text-emerald-900',
                close: 'text-emerald-400 hover:bg-emerald-100 hover:text-emerald-600 focus-visible:ring-emerald-500/40',
                track: 'bg-emerald-100',
                progress: 'bg-emerald-500',
            },
            error: {
                panel: 'border-rose-200 bg-rose-50/95',
                iconWrap: 'bg-rose-100 text-rose-600',
                message: 'text-rose-900',
                close: 'text-rose-400 hover:bg-rose-100 hover:text-rose-600 focus-visible:ring-rose-500/40',
                track: 'bg-rose-100',
                progress: 'bg-rose-500',
            },
            warning: {
                panel: 'border-amber-200 bg-amber-50/95',
                iconWrap: 'bg-amber-100 text-amber-600',
                message: 'text-amber-900',
                close: 'text-amber-400 hover:bg-amber-100 hover:text-amber-600 focus-visible:ring-amber-500/40',
                track: 'bg-amber-100',
                progress: 'bg-amber-500',
            },
            info: {
                panel: 'border-sky-200 bg-sky-50/95',
                iconWrap: 'bg-sky-100 text-sky-600',
                message: 'text-sky-900',
                close: 'text-sky-400 hover:bg-sky-100 hover:text-sky-600 focus-visible:ring-sky-500/40',
                track: 'bg-sky-100',
                progress: 'bg-sky-500',
            },
        };

        return themes[normalizeType(type)] || themes.info;
    },

    labelFor(type = 'info') {
        return {
            success: 'Sucesso',
            error: 'Erro',
            warning: 'Aviso',
            info: 'Informacao',
        }[normalizeType(type)] || 'Informacao';
    },

    progressStyle(toast) {
        return `animation: flash-toast-progress ${toast.duration}ms linear forwards;`;
    },
});

const Toast = {
    show(options = {}) {
        return emitToast(options);
    },
    success: createToastMethod('success'),
    error: createToastMethod('error'),
    warning: createToastMethod('warning'),
    info: createToastMethod('info'),
};

export default Toast;
