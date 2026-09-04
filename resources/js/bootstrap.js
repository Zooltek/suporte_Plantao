import axios from 'axios';

/**
 * Aplicando globalThis para atender à regra Sonar S7764.
 * Substitui o uso de 'window' para uma abordagem universal.
 */
globalThis.axios = axios;

globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';