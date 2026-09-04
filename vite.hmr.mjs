export const DEFAULT_VITE_HMR_HOST = 'localhost';

/**
 * Resolve o host anunciado pelo HMR do Vite.
 *
 * Mantido fora de `vite.config.js` para ser unitariamente testável e para
 * explicitar a política operacional do projeto:
 * - DEV local usa `localhost` por padrão;
 * - HMR compartilhado na LAN só acontece quando `VITE_HMR_HOST` é definido.
 */
export function resolveViteHmrHost(env = process.env) {
    const configuredHost = typeof env?.VITE_HMR_HOST === 'string'
        ? env.VITE_HMR_HOST.trim()
        : '';

    return configuredHost !== '' ? configuredHost : DEFAULT_VITE_HMR_HOST;
}
