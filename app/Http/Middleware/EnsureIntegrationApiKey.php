<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica requisições da integração com o sistema financeiro via API key.
 *
 * Referência OWASP Top 10:
 *  A07 — Identification & Authentication Failures → exige o cabeçalho X-API-Key
 *        e compara em tempo constante (hash_equals) para evitar timing attacks.
 *        O brute-force é contido pelo rate limiter `throttle:integration` (por IP),
 *        aplicado antes deste middleware na cadeia da rota.
 *  A04 — Insecure Design → fail-closed: se a chave não estiver configurada no
 *        servidor, o acesso é negado (nunca libera por omissão).
 *  A09 — Security Logging & Monitoring → registra tentativas inválidas com IP e
 *        rota, sem nunca vazar a chave esperada nem a fornecida.
 *  A02 — Cryptographic Failures → a chave só trafega sob TLS (borda Cloudflare) e
 *        não é logada nem devolvida na resposta. Mensagem de erro genérica não
 *        revela se a chave estava ausente ou incorreta.
 */
class EnsureIntegrationApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.financeiro.api_key', '');
        $provided = (string) $request->header('X-API-Key', '');

        // A04 — fail-closed: chave não configurada no servidor bloqueia o acesso.
        if ($expected === '') {
            Log::warning('[Integração Financeiro] API key não configurada no servidor; acesso bloqueado.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->unauthorized();
        }

        // A07 — comparação em tempo constante; mensagem genérica (não revela se a
        // chave estava ausente ou incorreta) para não auxiliar enumeração.
        if ($provided === '' || ! hash_equals($expected, $provided)) {
            Log::warning('[Integração Financeiro] Tentativa com API key inválida ou ausente.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return $this->unauthorized();
        }

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Credencial de integração inválida ou ausente.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
