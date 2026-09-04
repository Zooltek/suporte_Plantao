<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    private string $resetUrl;

    public function __construct(string $token, string $resetUrl)
    {
        parent::__construct($token);
        $this->resetUrl = $resetUrl;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        /** @var User $notifiable */
        return (new MailMessage)
            ->subject('Redefinição de Senha — Amura Suporte')
            ->view('emails.auth.reset-password', [
                'url'    => $this->resetUrl,
                'user'   => $notifiable,
                'expiry' => config('auth.passwords.admins.expire', 60),
            ]);
    }
}
