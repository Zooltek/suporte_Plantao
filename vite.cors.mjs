/**
 * Allowlist de origens autorizadas a consumir o Vite dev server.
 *
 * Mantida fora de `vite.config.js` para ser unitariamente testável.
 * Escopo: localhost + faixas privadas RFC1918 (10/8, 172.16/12, 192.168/16).
 * Wildcard (`cors: true`) é evitado para não reabrir a superfície da
 * CVE-2025-24010, que permitia sites externos lerem o código servido pelo
 * dev server.
 */
export const lanCorsOriginAllowlist = Object.freeze([
    /^https?:\/\/localhost(:\d+)?$/,
    /^https?:\/\/127\.0\.0\.1(:\d+)?$/,
    /^https?:\/\/10\.\d{1,3}\.\d{1,3}\.\d{1,3}(:\d+)?$/,
    /^https?:\/\/192\.168\.\d{1,3}\.\d{1,3}(:\d+)?$/,
    /^https?:\/\/172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}(:\d+)?$/,
]);

/**
 * Verifica se a origem HTTP pertence à faixa liberada para o dev server.
 * Espelha a avaliação feita pelo Vite (pacote `cors`) ao receber um array
 * de regex em `server.cors.origin`.
 */
export function isLanOrigin(origin) {
    if (typeof origin !== 'string' || origin === '') {
        return false;
    }
    return lanCorsOriginAllowlist.some((pattern) => pattern.test(origin));
}
