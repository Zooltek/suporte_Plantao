<?php

/**
 * Configuração dedicada do Redis para a aplicação Suporte.
 *
 * As definições de CONEXÃO ficam em config/database.php (padrão Laravel).
 * Este arquivo concentra configurações de NÍVEL DE APLICAÇÃO: TTLs,
 * namespaces de chaves, limites de rate limiting e documentação do
 * mapeamento de databases Redis.
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  DB  │ Conexão        │ Finalidade                             │
 * ├──────┼────────────────┼────────────────────────────────────────┤
 * │  0   │ default        │ Operações gerais / broadcasting        │
 * │  1   │ cache          │ Cache da aplicação (CACHE_STORE=redis) │
 * │  2   │ queue          │ Filas de jobs (QUEUE_CONNECTION=redis) │
 * │  3   │ session        │ Sessões de usuários                    │
 * │  4   │ rate_limiter   │ Contadores de throttle / rate limiting │
 * └──────┴────────────────┴────────────────────────────────────────┘
 */

return [

    /*
    |--------------------------------------------------------------------------
    | TTLs (segundos)
    |--------------------------------------------------------------------------
    | Tempos de expiração padronizados por categoria de dado.
    | Uso: cache()->put($key, $value, config('redis.ttl.default'));
    */

    'ttl' => [
        'short' => env('REDIS_TTL_SHORT', 300),    // 5 min  — dados voláteis (listas, contadores)
        'default' => env('REDIS_TTL_DEFAULT', 3600),   // 1 h    — cache padrão
        'long' => env('REDIS_TTL_LONG', 86400),  // 24 h   — dados estáveis (categorias, configs)
        'session' => env('REDIS_TTL_SESSION', 36000),  // 10 h   — sessões de usuário
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits (requisições por minuto)
    |--------------------------------------------------------------------------
    | Thresholds centralizados. Consumidos pelo RateLimitServiceProvider.
    | OWASP A07 — Authentication Failures / A04 — Insecure Design.
    */

    'rate_limits' => [
        'api' => env('RATE_LIMIT_API', 60),  // APIs gerais (routes/api.php)
        'api_strict' => env('RATE_LIMIT_API_STRICT', 30),  // Operações de escrita/sensíveis
        'reports' => env('RATE_LIMIT_REPORTS', 10),  // Relatórios pesados
        'tasks_api' => env('RATE_LIMIT_TASKS_API', 120),  // API de tarefas (alta frequência)
        'admin_api' => env('RATE_LIMIT_ADMIN_API', 60),  // Painel admin (CRUD via JS)
        'whatsapp_webhook' => env('RATE_LIMIT_WHATSAPP_WEBHOOK', 300), // Webhook Evolution/WhatsApp
        'integration' => env('RATE_LIMIT_INTEGRATION', 60),  // Integração financeiro (por IP — A07/A04)
        'password_reset' => env('RATE_LIMIT_PASSWORD_RESET', 3),  // Reset de senha (por IP)
        'auth' => env('RATE_LIMIT_AUTH', 5),  // Login (por IP)
    ],

    /*
    |--------------------------------------------------------------------------
    | Namespaces de Chaves
    |--------------------------------------------------------------------------
    | Prefixos para organizar o keyspace do Redis por módulo.
    | Evita colisões e facilita flush seletivo por módulo.
    */

    'prefixes' => [
        'sla' => 'sla:',
        'tickets' => 'ticket:',
        'schedules' => 'schedule:',
        'tasks' => 'task:',
        'agents' => 'agent:',
        'categories' => 'cat:',
    ],

];
