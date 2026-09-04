<?php

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

function viteHotFixturePath(): string
{
    return sys_get_temp_dir().'/suporte-vite.hot';
}

function runSecurityHeaders(): Response
{
    $middleware = new SecurityHeadersMiddleware;
    $request = Request::create('/teste', 'GET');

    /** @var Response $response */
    $response = $middleware->handle($request, function () {
        $r = new Response('ok', 200);
        // Headers que o middleware deve remover — semeamos aqui para provar a remoção.
        $r->headers->set('X-Powered-By', 'PHP/8.4');
        $r->headers->set('Server', 'nginx/1.27');

        return $r;
    });

    return $response;
}

describe('SecurityHeadersMiddleware — cabeçalhos fixos (OWASP A01/A02/A05)', function () {
    it('define X-Frame-Options = SAMEORIGIN (A01 — clickjacking)', function () {
        $response = runSecurityHeaders();
        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    });

    it('define X-Content-Type-Options = nosniff (A02 — MIME sniffing)', function () {
        $response = runSecurityHeaders();
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    });

    it('define Referrer-Policy = strict-origin-when-cross-origin (A02)', function () {
        $response = runSecurityHeaders();
        expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    });

    it('define Permissions-Policy bloqueando APIs sensíveis (A05)', function () {
        $response = runSecurityHeaders();
        $policy = $response->headers->get('Permissions-Policy');

        expect($policy)
            ->toContain('camera=()')
            ->toContain('microphone=(self)') // permitido para gravação de áudio no WhatsApp
            ->toContain('geolocation=()')
            ->toContain('payment=()')
            ->toContain('usb=()');
    });

    it('remove X-Powered-By e Server para evitar fingerprinting (A05)', function () {
        $response = runSecurityHeaders();

        expect($response->headers->has('X-Powered-By'))->toBeFalse();
        expect($response->headers->has('Server'))->toBeFalse();
    });
});

describe('SecurityHeadersMiddleware — HSTS depende de ambiente (A02)', function () {
    it('NÃO envia Strict-Transport-Security fora de produção', function () {
        app()->detectEnvironment(fn () => 'local');

        $response = runSecurityHeaders();

        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

    it('envia HSTS com max-age=1 ano, includeSubDomains e preload em produção', function () {
        app()->detectEnvironment(fn () => 'production');

        $response = runSecurityHeaders();
        $hsts = $response->headers->get('Strict-Transport-Security');

        expect($hsts)
            ->toContain('max-age=31536000')
            ->toContain('includeSubDomains')
            ->toContain('preload');
    });
});

describe('SecurityHeadersMiddleware — Content Security Policy (A03/A05)', function () {
    it('inclui diretivas obrigatórias com defaults restritivos', function () {
        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)
            ->toContain("default-src 'self'")
            ->toContain("frame-ancestors 'self'")
            ->toContain("base-uri 'self'")
            ->toContain("form-action 'self'")
            ->toContain("object-src 'none'")
            ->toContain("img-src 'self' data: blob:");
    });

    it('permite as fontes externas oficiais (Google Fonts, cdnjs)', function () {
        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)
            ->toContain('https://fonts.googleapis.com')
            ->toContain('https://fonts.gstatic.com')
            ->toContain('https://cdnjs.cloudflare.com');
    });

    it('permite WebSocket e API do Pusher em connect-src', function () {
        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)
            ->toContain('wss://*.pusher.com')
            ->toContain('https://*.pusher.com');
    });
});

describe('SecurityHeadersMiddleware — Vite HMR liberado apenas em dev (regressão CSP font-src)', function () {
    beforeEach(function () {
        // Isola o teste do .env local: host padrão (localhost) deve valer salvo override explícito.
        putenv('VITE_HMR_HOST');
        unset($_ENV['VITE_HMR_HOST'], $_SERVER['VITE_HMR_HOST']);
        app(Vite::class)->useHotFile(viteHotFixturePath());

        if (is_file(viteHotFixturePath())) {
            unlink(viteHotFixturePath());
        }
    });

    afterEach(function () {
        if (is_file(viteHotFixturePath())) {
            unlink(viteHotFixturePath());
        }

        app(Vite::class)->useHotFile(public_path('hot'));
    });

    it('em dev não libera HMR no CSP quando o projeto está servindo assets compilados', function () {
        app()->detectEnvironment(fn () => 'local');

        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)
            ->not->toContain('localhost:5173')
            ->not->toContain('ws://localhost:5173')
            ->not->toContain('http://192.168.0.15:5173')
            ->not->toContain('ws://192.168.0.15:5173');
    });

    it('em dev libera localhost:5173 em script-src, style-src, font-src e connect-src quando o HMR está ativo', function () {
        app()->detectEnvironment(fn () => 'local');
        file_put_contents(viteHotFixturePath(), 'http://localhost:5173');

        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        // Extrai cada diretiva para validar o escopo exato em que o Vite aparece.
        $directives = collect(explode('; ', $csp))
            ->mapWithKeys(function (string $line) {
                [$name, $value] = array_pad(explode(' ', $line, 2), 2, '');

                return [$name => $value];
            });

        expect($directives['script-src'])->toContain('http://localhost:5173');
        expect($directives['style-src'])->toContain('http://localhost:5173');
        expect($directives['font-src'])->toContain('http://localhost:5173');  // regressão principal
        expect($directives['connect-src'])
            ->toContain('http://localhost:5173')
            ->toContain('ws://localhost:5173');
    });

    it('em dev respeita VITE_HMR_HOST customizado quando o HMR está ativo', function () {
        app()->detectEnvironment(fn () => 'local');
        putenv('VITE_HMR_HOST=192.168.0.15');
        file_put_contents(viteHotFixturePath(), 'http://192.168.0.15:5173');

        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)
            ->toContain('http://192.168.0.15:5173')
            ->toContain('ws://192.168.0.15:5173')
            ->not->toContain('localhost:5173');
    });

    it('em produção NÃO libera Vite HMR em nenhuma diretiva', function () {
        app()->detectEnvironment(fn () => 'production');
        putenv('VITE_HMR_HOST=192.168.0.15'); // ainda assim não deve vazar em prod

        $response = runSecurityHeaders();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)
            ->not->toContain('localhost:5173')
            ->not->toContain('192.168.0.15:5173')
            ->not->toContain('http://localhost')
            ->not->toContain('ws://localhost');
    });
});
