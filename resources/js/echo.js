import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Aplicando globalThis para atender à regra Sonar S7764.
 * O Pusher precisa estar no escopo global para o Laravel Echo encontrá-lo.
 */
const pusherKey = (import.meta.env.VITE_PUSHER_APP_KEY ?? '').trim();

if (pusherKey !== '') {
    globalThis.Pusher = Pusher;

    globalThis.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        forceTLS: true,
        wsHost: import.meta.env.VITE_PUSHER_HOST,
        wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
        wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
        enabledTransports: ['ws', 'wss'],
    });
} else {
    globalThis.Echo = undefined;

    if (import.meta.env.DEV) {
        console.info('[Echo] Inicialização ignorada: VITE_PUSHER_APP_KEY não configurada.');
    }
}
