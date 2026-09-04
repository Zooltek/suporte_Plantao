<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica cabeçalhos de segurança HTTP em todas as respostas.
 *
 * Referência OWASP Top 10:
 *  A01 — Broken Access Control     → X-Frame-Options (clickjacking)
 *  A02 — Cryptographic Failures    → HSTS (downgrade), X-Content-Type-Options
 *  A03 — Injection                 → CSP (base-uri, form-action)
 *  A05 — Security Misconfiguration → CSP, Permissions-Policy, remoção de fingerprint
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // A01 — Impede framing por origens externas (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // A02 — Impede MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // A02 — Controla informações de Referer enviadas a terceiros
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // A05 — Remove fingerprint do servidor PHP
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // A05 — Restringe APIs de browser desnecessárias
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(self), geolocation=(), payment=(), usb=()'
        );

        // A02 — HSTS: força HTTPS por 1 ano (apenas em produção)
        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // A03 + A05 — Content Security Policy
        // 'unsafe-inline' é necessário enquanto Alpine.js usar x-data inline.
        // CDNs externos declarados explicitamente por diretiva para minimizar superfície.
        $isProd = app()->isProduction();

        // Fontes externas: Google Fonts (estilos + fontes) e Font Awesome via cdnjs
        $styleSrc  = "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com";
        $fontSrc   = "'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com";

        // Scripts externos: Tailwind CDN e jsDelivr usados nas views de relatório.
        // 'unsafe-eval' necessário: Alpine.js usa new Function() para avaliar expressões e
        // template literals em diretivas (x-bind, x-on, etc.). Remover unsafe-eval sem
        // migrar para @alpinejs/csp + nonces quebraria toda a aplicação.
        // Nota: enquanto 'unsafe-inline' estiver presente, unsafe-eval não representa risco
        // incremental real — um atacante com XSS já pode executar eval() via <script> inline.
        // Migração completa para CSP restrito requer sprint dedicado (nonces + csp build).
        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net";

        // Pusher WebSocket: wss://ws-{cluster}.pusher.com + API REST + SockJS fallback
        $connectSrc = "'self' wss://*.pusher.com https://*.pusher.com";

        // Em desenvolvimento, libera o servidor Vite apenas quando o projeto
        // está realmente rodando com HMR e a política de request permite usar
        // o hot file. A checagem de origem local fica centralizada em
        // NetworkAwareVite::isRunningHot().
        if (! $isProd && app(Vite::class)->isRunningHot()) {
            $hmrHost    = trim((string) env('VITE_HMR_HOST', '')) ?: 'localhost';
            $viteHttp   = "http://{$hmrHost}:5173";
            $viteWs     = "ws://{$hmrHost}:5173";
            $scriptSrc  .= " $viteHttp";
            $styleSrc   .= " $viteHttp";
            $fontSrc    .= " $viteHttp";
            $connectSrc .= " $viteHttp $viteWs";
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src $scriptSrc",
            "style-src $styleSrc",
            "img-src 'self' data: blob: https://*.amazonaws.com https://*.notion.so https://*.notion.site https://images.unsplash.com https://*.googleusercontent.com https://*.githubusercontent.com",
            "media-src 'self' data: blob: https://*.amazonaws.com https://*.notion.so https://*.notion.site",
            "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.loom.com",
            "font-src $fontSrc",
            "connect-src $connectSrc",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
