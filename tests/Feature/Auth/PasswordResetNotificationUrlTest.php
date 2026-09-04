<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

test('recuperacao de senha usa o host por IP da requisicao quando o app_url esta em localhost', function () {
    Notification::fake();
    config()->set('app.url', 'http://localhost:8090');

    $user = User::factory()->create();
    app()->instance('request', Request::create('http://192.168.0.15:8090/forgot-password', 'POST'));

    $user->sendPasswordResetNotification('token-ip');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) {
        $resetUrl = passwordResetNotificationUrl($notification);

        expect($resetUrl)
            ->toStartWith('http://192.168.0.15:8090/admin/reset-password/')
            ->not->toStartWith('http://localhost:8090');

        return true;
    });
});

test('recuperacao de senha preserva o app_url publico quando ele ja esta configurado', function () {
    Notification::fake();
    config()->set('app.url', 'https://suporte.amura.com.br');

    $user = User::factory()->create();
    app()->instance('request', Request::create('http://192.168.0.15:8090/forgot-password', 'POST'));

    $user->sendPasswordResetNotification('token-publico');

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) {
        $resetUrl = passwordResetNotificationUrl($notification);

        expect($resetUrl)->toStartWith('https://suporte.amura.com.br/admin/reset-password/');

        return true;
    });
});

function passwordResetNotificationUrl(ResetPasswordNotification $notification): string
{
    $property = new \ReflectionProperty($notification, 'resetUrl');
    $property->setAccessible(true);

    return (string) $property->getValue($notification);
}
