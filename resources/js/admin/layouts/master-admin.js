// resources/js/admin-core.js

/**
 * Utilitário para formatar JSON de erro vindo do Laravel
 * @param {Object|String} json 
 * @returns {String}
 */
const formatJsonError = (json) => {
    if (typeof json !== 'object' || json === null) {
        return json == null ? '' : String(json);
    }

    return Object.entries(json)
        .map(([key, value]) => `${key}: ${Array.isArray(value) ? value.join(', ') : value}`)
        .join(' | ');
};

/**
 * Exibe notificações de erro usando o host global de toast
 * Atribuído ao globalThis para conformidade Sonar S7764
 */
globalThis.showError = (msg = '', responseCode = '') => {
    const prefix = responseCode ? `Erro ${responseCode}` : 'Erro';
    const message = formatJsonError(msg);
    const formattedMessage = message ? `${prefix}: ${message}` : prefix;
    const method = globalThis.AppToast?.error || globalThis.AppToast?.show;

    if (typeof method === 'function') {
        method({ message: formattedMessage, type: 'error', duration: 7000 });
        return;
    }

    console.error(prefix, msg);
};

if (globalThis.axios) {
    globalThis.axios.interceptors.response.use(
        response => response,
        error => {
            const status = error.response ? error.response.status : 'Network Error';
            const data = error.response ? error.response.data : error.message;
            globalThis.showError(data, status);
            return Promise.reject(error);
        }
    );
}
