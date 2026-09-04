<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/crm.php'));

            Route::middleware('web')
                ->group(base_path('routes/agent.php'));

            Route::prefix('portal')
                ->name('portal.')
                ->middleware('web')
                ->group(base_path('routes/helpdesk.php'));

            Route::middleware('api')
                ->group(base_path('routes/task.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // A aplicação é exposta exclusivamente via Cloudflare Tunnel (cloudflared),
        // que conecta na origem por HTTP simples e envia os cabeçalhos X-Forwarded-*.
        // Como a origem (127.0.0.1:8090 / suporte12_app:8080) não é acessível
        // diretamente da internet, é seguro confiar no proxy para detectar HTTPS.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('portal*') || $request->is('cliente*')
                ? route('login')
                : route('admin.login')
        );

        // Define para onde usuários logados vão se tentarem acessar páginas de guest (como login)
        $middleware->redirectUsersTo(function (Request $request) {
            $user = \Illuminate\Support\Facades\Auth::guard('admin')->user()
                 ?? \Illuminate\Support\Facades\Auth::user();

            if (! $user) {
                return route('login');
            }

            $access = app(\App\Services\Access\AccessService::class);

            if ($access->isAdmin($user)) {
                return route('admin.dashboard');
            }

            if ($access->isCrmDepartment($user) || $access->isCrmEmailUser($user)) {
                return route('crm.index');
            }

            if ($access->hasStaffAccess($user)) {
                return route('agent.index');
            }

            return route('portal.home');
        });

        $middleware->validateCsrfTokens(except: [
            'logout',
            'admin/logout',
            '*/logout',
            'chat/disconnect',
            'chat/admins',
            'chat/validate',
            'chat/expire/active/sessions',
            'chat/expire/disconnected/sessions',
            'chat/authenticate',
        ]);

        // Cabeçalhos de segurança em todas as respostas (OWASP A01/A02/A03/A05)
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);

        // Rastreamento de atividade recente de usuários logados (Dashboard TV / Online)
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackUserActivity::class);

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,

            // Segurança
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,

            // Middlewares customizados da aplicação
            'agent' => \App\Http\Middleware\AgentMiddleware::class,
            'user' => \App\Http\Middleware\Helpdesk\IsAgent::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'implementation' => \App\Http\Middleware\CanManageImplementation::class,
            'feedback' => \App\Http\Middleware\FeedbackMiddleware::class,
            'helpdesk.admin' => \App\Http\Middleware\HelpdeskAdmin::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'integration.apikey' => \App\Http\Middleware\EnsureIntegrationApiKey::class,

            // Aliases do pacote TicketSystem (migrados para nativo)
            'is_agent' => \App\Http\Middleware\Helpdesk\IsAgent::class,
            'is_admin' => \App\Http\Middleware\Helpdesk\IsAdmin::class,
            'res_access' => \App\Http\Middleware\Helpdesk\ResAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $e): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->is('logout') || $request->is('admin/logout') || $request->is('*/logout')) {
                if (\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
                    \Illuminate\Support\Facades\Auth::guard('admin')->logout();
                }
                if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
                    \Illuminate\Support\Facades\Auth::guard('web')->logout();
                }
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sua sessão expirou por inatividade. Por favor, recarregue a página.',
                ], 419);
            }

            $request->session()->regenerateToken();

            return redirect()->route('login')->with('warning', 'Sua sessão expirou por inatividade. Por favor, faça login novamente.');
        });
    })
    ->create();
