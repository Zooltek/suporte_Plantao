<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Mail\Transport\ArrayTransport;
use Symfony\Component\Mime\Email;

test('reset password email embeds brand images inline for email clients', function () {
    config()->set('mail.default', 'array');

    $mailer = app('mail.manager')->mailer('array');

    /** @var ArrayTransport $transport */
    $transport = $mailer->getSymfonyTransport();
    $transport->flush();

    $user = new User([
        'name' => 'Usuaria Teste',
        'email' => 'teste@example.com',
    ]);

    $user->notify(new ResetPasswordNotification(
        'token-de-teste',
        'https://example.com/admin/reset-password/token-de-teste?email=teste@example.com'
    ));

    $sentMessage = $transport->messages()->sole();

    /** @var Email $originalMessage */
    $originalMessage = $sentMessage->getOriginalMessage();
    $html = (string) $originalMessage->getHtmlBody();

    expect($html)
        ->toContain('cid:')
        ->toContain('Amura Suporte')
        ->not->toContain('img/amura-logo-light.png')
        ->not->toContain('data:image/svg+xml');

    expect(collect($originalMessage->getAttachments()))->toHaveCount(2);
});
