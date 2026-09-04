<?php

use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\EvolutionInstanceService;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

beforeEach(function () {
    config([
        'whatsapp.enabled' => true,
        'whatsapp.provider' => 'evolution',
        'whatsapp.api_url' => 'http://suporte12_evolution:8080',
        'whatsapp.evolution_instance' => 'amura-local',
        'whatsapp.evolution_api_key' => 'test-key-123',
        'whatsapp.local_test_numbers' => ['27981180125', '45999178290'],
    ]);
});

it('falha quando a instancia evolution nao esta conectada e allow-disconnected nao foi informado', function () {
    $mock = Mockery::mock(EvolutionInstanceService::class);
    $mock->shouldReceive('connectionState')
        ->once()
        ->andReturn(['state' => 'close', 'instance' => 'amura-local']);

    $this->app->instance(EvolutionInstanceService::class, $mock);

    $this->artisan('whatsapp:homologacao', [
        '--phone' => '5527999990000',
    ])
        ->expectsOutputToContain('Instância Evolution não está conectada')
        ->assertExitCode(SymfonyCommand::FAILURE);
});

it('usa o numero local padrao e lista os numeros de homologacao quando nenhum telefone e informado', function () {
    $mock = Mockery::mock(EvolutionInstanceService::class);
    $mock->shouldReceive('connectionState')
        ->once()
        ->andReturn(['state' => 'close', 'instance' => 'amura-local']);

    $this->app->instance(EvolutionInstanceService::class, $mock);

    $this->artisan('whatsapp:homologacao')
        ->expectsOutputToContain('Usando número local padrão +27981180125')
        ->expectsOutputToContain('Instância Evolution não está conectada')
        ->assertExitCode(SymfonyCommand::FAILURE);
});

it('falha quando o telefone informado ja possui conversa ativa em estado intermediario', function () {
    $mock = Mockery::mock(EvolutionInstanceService::class);
    $mock->shouldReceive('connectionState')
        ->once()
        ->andReturn(['state' => 'open', 'instance' => 'amura-local']);

    $this->app->instance(EvolutionInstanceService::class, $mock);

    WhatsAppConversation::factory()->awaitingName()->create([
        'phone' => '5527999990000',
    ]);

    $this->artisan('whatsapp:homologacao', [
        '--phone' => '5527999990000',
    ])
        ->expectsOutputToContain('já possui uma conversa ativa em andamento')
        ->assertExitCode(SymfonyCommand::FAILURE);
});
