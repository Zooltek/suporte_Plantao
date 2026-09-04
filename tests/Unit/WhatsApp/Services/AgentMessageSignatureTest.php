<?php

use App\Models\User;
use App\Services\WhatsApp\AgentMessageSignature;

beforeEach(function () {
    $this->signature = new AgentMessageSignature;
    config([
        'whatsapp.agent_signature.enabled' => true,
        'whatsapp.agent_signature.template' => "*{name}*\n{message}",
    ]);
});

it('prefixa o corpo com o nome do agente quando habilitada', function () {
    $agent = User::factory()->make(['name' => 'Maria Souza']);

    expect($this->signature->apply('Olá, tudo bem?', $agent))
        ->toBe("*Maria Souza*\nOlá, tudo bem?");
});

it('retorna o corpo intacto quando a assinatura está desligada', function () {
    config(['whatsapp.agent_signature.enabled' => false]);
    $agent = User::factory()->make(['name' => 'Maria Souza']);

    expect($this->signature->apply('Olá', $agent))->toBe('Olá');
});

it('retorna o corpo intacto quando o agente não possui nome', function () {
    $agent = User::factory()->make(['name' => '   ']);

    expect($this->signature->apply('Olá', $agent))->toBe('Olá');
});

it('suporta o placeholder first_name', function () {
    config(['whatsapp.agent_signature.template' => '{first_name}: {message}']);
    $agent = User::factory()->make(['name' => 'João Pedro Almeida']);

    expect($this->signature->apply('Bom dia', $agent))->toBe('João: Bom dia');
});

it('não deixa quebra de linha solta quando a mensagem é vazia (mídia sem legenda)', function () {
    $agent = User::factory()->make(['name' => 'Maria Souza']);

    expect($this->signature->apply('', $agent))->toBe('*Maria Souza*');
});

it('applyToCaption retorna a legenda assinada quando há texto', function () {
    $agent = User::factory()->make(['name' => 'Maria Souza']);

    expect($this->signature->applyToCaption('Veja o anexo', $agent))
        ->toBe("*Maria Souza*\nVeja o anexo");
});

it('applyToCaption assina apenas o nome quando a legenda é nula', function () {
    $agent = User::factory()->make(['name' => 'Maria Souza']);

    expect($this->signature->applyToCaption(null, $agent))->toBe('*Maria Souza*');
});

it('applyToCaption retorna null quando a assinatura está desligada e a legenda é nula', function () {
    config(['whatsapp.agent_signature.enabled' => false]);
    $agent = User::factory()->make(['name' => 'Maria Souza']);

    expect($this->signature->applyToCaption(null, $agent))->toBeNull();
});
