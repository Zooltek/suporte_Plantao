<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    private const DELIVERY_ERROR_MESSAGE = 'Não foi possível enviar o e-mail de redefinição no momento. Tente novamente mais tarde ou contate o suporte.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::sendResetLink($credentials);
        } catch (TransportExceptionInterface $exception) {
            Log::error('Falha ao enviar e-mail de redefinição de senha.', [
                'route' => $request->route()?->getName(),
                'request_host' => $request->getHost(),
                'request_ip' => $request->ip(),
                'mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_scheme' => config('mail.mailers.smtp.scheme'),
                'exception_class' => $exception::class,
                'smtp_code' => $exception->getCode(),
            ]);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => self::DELIVERY_ERROR_MESSAGE]);
        }

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
