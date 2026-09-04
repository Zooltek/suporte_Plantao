<?php

use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;

test('recuperacao de senha publica retorna erro amigavel quando o SMTP falha', function () {
    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'teste@example.com'])
        ->andThrow(new TransportException('535 authentication failed'));

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'teste@example.com'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors([
            'email' => 'Não foi possível enviar o e-mail de redefinição no momento. Tente novamente mais tarde ou contate o suporte.',
        ]);
});

test('recuperacao de senha administrativa retorna erro amigavel quando o SMTP falha', function () {
    $broker = \Mockery::mock();

    Password::shouldReceive('broker')
        ->once()
        ->with('admins')
        ->andReturn($broker);

    $broker->shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => 'admin@example.com'])
        ->andThrow(new TransportException('535 authentication failed'));

    $this->from(route('admin.password.request'))
        ->post(route('admin.password.email'), ['email' => 'admin@example.com'])
        ->assertRedirect(route('admin.password.request'))
        ->assertSessionHasErrors([
            'email' => 'Não foi possível enviar o e-mail de redefinição no momento. Tente novamente mais tarde ou contate o suporte.',
        ]);
});
