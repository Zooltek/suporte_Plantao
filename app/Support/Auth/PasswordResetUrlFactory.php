<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;

class PasswordResetUrlFactory
{
    public function make(User $user, string $token): string
    {
        $path = route('admin.password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], false);

        return $this->resolveBaseUrl().$path;
    }

    private function resolveBaseUrl(): string
    {
        $configuredBaseUrl = rtrim((string) config('app.url', url('/')), '/');
        $request = request();

        if (!$request instanceof Request) {
            return $configuredBaseUrl;
        }

        $requestHttpHost = $this->resolveRequestHttpHost($request);
        $requestHost = parse_url('//'.$requestHttpHost, PHP_URL_HOST);

        if (!is_string($requestHost) || !$this->shouldPreferRequestBaseUrl($configuredBaseUrl, $requestHost)) {
            return $configuredBaseUrl;
        }

        return $request->getScheme().'://'.$requestHttpHost;
    }

    private function shouldPreferRequestBaseUrl(string $configuredBaseUrl, string $requestHost): bool
    {
        $configuredHost = parse_url($configuredBaseUrl, PHP_URL_HOST);

        if (!is_string($configuredHost)) {
            return false;
        }

        return $this->isLocalhost($configuredHost)
            && filter_var($requestHost, FILTER_VALIDATE_IP) !== false;
    }

    private function resolveRequestHttpHost(Request $request): string
    {
        $host = (string) ($request->headers->get('host')
            ?? $request->server('HTTP_HOST')
            ?? $request->getHttpHost());

        return trim($host) !== ''
            ? rtrim($host, '/')
            : $request->getHttpHost();
    }

    private function isLocalhost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
