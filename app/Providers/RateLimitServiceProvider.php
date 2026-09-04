<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Support\ServiceProvider;

/**
 * Configura os rate limiters nomeados da aplicação.
 *
 * A store usada pelo ThrottleRequests é definida em config/cache.php
 * via cache.limiter, porque o binding do framework é registrado pelo
 * CacheServiceProvider quando o serviço é resolvido.
 *
 * Nomes dos limiters → uso nas rotas:
 *  throttle:api              — APIs Sanctum gerais (routes/api.php)
 *  throttle:api:strict       — Escritas sensíveis (attendances, sync, finalize)
 *  throttle:api:reports      — Relatórios pesados (alto custo de CPU/DB)
 *  throttle:tasks:api        — API de tarefas (alta frequência de polling)
 *  throttle:admin:api        — APIs do painel admin (CRUD via JS)
 *  throttle:whatsapp:webhook — Webhooks de provedores WhatsApp
 *  throttle:integration      — Integração financeiro (A07/A04 — brute-force de API key, por IP)
 *  throttle:admin:password-reset — Reset de senha (A07 — brute-force)
 *  throttle:auth             — Autenticação (A07 — credential stuffing)
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        $limits = config('redis.rate_limits');

        // ── API Geral (Sanctum) ──────────────────────────────────────────────
        // OWASP A04 — Insecure Design: limita abuso de endpoints de leitura
        RateLimiterFacade::for('api', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['api'])
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Muitas requisições. Aguarde antes de tentar novamente.',
                    'retry_after' => RateLimiterFacade::availableIn(
                        'api|'.($request->user()?->id ?: $request->ip())
                    ),
                ], 429));
        });

        // ── Operações de Escrita Sensíveis ───────────────────────────────────
        // (attendances store, schedule finalize, record sync)
        RateLimiterFacade::for('api:strict', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['api_strict'])
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Limite de operações sensíveis atingido. Aguarde um minuto.',
                ], 429));
        });

        // ── Relatórios (alto custo) ──────────────────────────────────────────
        RateLimiterFacade::for('api:reports', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['reports'])
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Limite de geração de relatórios atingido. Aguarde um minuto.',
                ], 429));
        });

        // ── Task API (alta frequência de polling de notificações) ─────────────
        RateLimiterFacade::for('tasks:api', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['tasks_api'])
                ->by($request->user()?->id ?: $request->ip());
        });

        // ── Admin Panel APIs ─────────────────────────────────────────────────
        RateLimiterFacade::for('admin:api', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['admin_api'])
                ->by($request->user()?->id ?: $request->ip());
        });

        // ── WhatsApp Webhook ────────────────────────────────────────────────
        // Alto o suficiente para lotes da Evolution, mas ainda limitado por chave/IP.
        RateLimiterFacade::for('whatsapp:webhook', function (Request $request) use ($limits) {
            $apiKey = (string) ($request->header('apikey') ?: $request->query('apikey', ''));
            $identity = $apiKey !== ''
                ? 'key:'.hash('sha256', $apiKey)
                : 'ip:'.$request->ip();

            return Limit::perMinute($limits['whatsapp_webhook'])
                ->by($identity)
                ->response(fn () => response()->json([
                    'message' => 'Limite de webhooks WhatsApp atingido. Aguarde um minuto.',
                ], 429));
        });

        // ── Integração Financeiro — A07/A04: Brute-Force de API key ──────────
        // Por IP (e não por chave) para que tentativas com chaves diferentes a
        // partir do mesmo IP sejam contidas. Roda antes do EnsureIntegrationApiKey.
        RateLimiterFacade::for('integration', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['integration'])
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Limite de requisições da integração atingido. Aguarde um minuto.',
                ], 429));
        });

        // ── Reset de Senha — A07: Authentication Failures ────────────────────
        // Limita por IP para prevenir enumeração de e-mails e ataques de flooding
        RateLimiterFacade::for('admin:password-reset', function (Request $request) use ($limits) {
            return Limit::perMinute($limits['password_reset'])
                ->by($request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Muitas tentativas de reset. Tente novamente em alguns minutos.',
                ], 429));
        });

        // ── Autenticação — A07: Credential Stuffing / Brute-Force ────────────
        // Dupla camada: por IP e por usuário (caso já autenticado tentando relogar)
        RateLimiterFacade::for('auth', function (Request $request) use ($limits) {
            return [
                Limit::perMinute($limits['auth'])->by($request->ip()),
                Limit::perMinute($limits['auth'] * 3)->by($request->input('email').'|'.$request->ip()),
            ];
        });
    }
}
