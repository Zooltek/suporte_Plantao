<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppMessageMacro;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function agent_whatsapp_ticket(User $agent): array
{
    $ticket = Ticket::factory()->create([
        'author_id' => $agent->id,
        'user_id' => $agent->id,
        'agent_id' => $agent->id,
        'department_id' => $agent->department_id,
    ]);

    $conversation = WhatsAppConversation::factory()->humanPending()->create([
        'ticket_id' => $ticket->id,
    ]);

    return [$ticket, $conversation];
}

it('lista mensagens novas da conversa do chamado WhatsApp sem duplicar por after_id', function () {
    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    $old = WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Mensagem antiga',
    ]);
    WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'body' => 'Mensagem nova',
    ]);

    $this->getJson(route('agent.ticket.whatsapp.messages', [$ticket, 'after_id' => $old->id]))
        ->assertOk()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.body', 'Mensagem nova');
});

it('envia mensagem externa com emoji pelo WhatsApp do chamado', function () {
    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('send')
        ->once()
        ->with(
            Mockery::on(fn ($value) => (int) $value->id === (int) $conversation->id),
            'Olá 🙂',
            $agent->id,
            false,
            "*{$agent->name}*\nOlá 🙂",
        )
        ->andReturnUsing(function (WhatsAppConversation $conversation, string $body, int $userId): WhatsAppMessage {
            return WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $body,
            ]);
        });
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'message' => 'Olá 🙂',
    ])
        ->assertCreated()
        ->assertJsonPath('message.body', 'Olá 🙂')
        ->assertJsonPath('message.is_internal', false);
});

it('salva mensagem interna sem enviar ao provider WhatsApp', function () {
    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('send')->never();
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'message' => 'Observação somente interna',
        'internal' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('message.body', 'Observação somente interna')
        ->assertJsonPath('message.is_internal', true);

    $this->assertDatabaseHas('whatsapp_messages', [
        'body' => 'Observação somente interna',
        'is_internal' => true,
    ]);
});

it('substitui atalho ativo antes de enviar e não envia comando literal', function () {
    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    WhatsAppMessageMacro::query()->create([
        'command' => '/sefaz',
        'text' => 'Sefaz fora do ar favor aguardar.',
        'department_id' => $agent->department_id,
        'is_active' => true,
    ]);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('send')
        ->once()
        ->with(
            Mockery::on(fn ($value) => (int) $value->id === (int) $conversation->id),
            'Sefaz fora do ar favor aguardar.',
            $agent->id,
            false,
            "*{$agent->name}*\nSefaz fora do ar favor aguardar.",
        )
        ->andReturnUsing(function (WhatsAppConversation $conversation, string $body, int $userId): WhatsAppMessage {
            return WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $body,
            ]);
        });
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'message' => '/sefaz',
    ])->assertCreated();

    $this->assertDatabaseMissing('whatsapp_messages', ['body' => '/sefaz']);
});

it('recusa atalho inexistente sem enviar comando literal ao cliente', function () {
    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('send')->never();
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'message' => '/naoexiste',
    ])->assertUnprocessable();

    $this->assertDatabaseMissing('whatsapp_messages', ['body' => '/naoexiste']);
});

it('assina o texto entregue ao cliente com o nome do agente mantendo o corpo do histórico sem prefixo', function () {
    config(['whatsapp.agent_signature.enabled' => true]);

    $agent = actingAsAgent();
    $agent->forceFill(['name' => 'João Silva'])->save();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    $providerText = null;
    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('send')
        ->once()
        ->andReturnUsing(function (WhatsAppConversation $conversation, string $body, int $userId, bool $internal, ?string $signed) use (&$providerText): WhatsAppMessage {
            $providerText = $signed;

            return WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $body,
            ]);
        });
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'message' => 'Bom dia, segue o retorno.',
    ])
        ->assertCreated()
        ->assertJsonPath('message.body', 'Bom dia, segue o retorno.');

    expect($providerText)->toBe("*João Silva*\nBom dia, segue o retorno.");
    $this->assertDatabaseHas('whatsapp_messages', ['body' => 'Bom dia, segue o retorno.']);
    $this->assertDatabaseMissing('whatsapp_messages', ['body' => "*João Silva*\nBom dia, segue o retorno."]);
});

it('não assina o texto quando a assinatura do atendente está desligada', function () {
    config(['whatsapp.agent_signature.enabled' => false]);

    $agent = actingAsAgent();
    $agent->forceFill(['name' => 'João Silva'])->save();
    [$ticket] = agent_whatsapp_ticket($agent);

    $providerText = null;
    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('send')
        ->once()
        ->andReturnUsing(function (WhatsAppConversation $conversation, string $body, int $userId, bool $internal, ?string $signed) use (&$providerText): WhatsAppMessage {
            $providerText = $signed;

            return WhatsAppMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $body,
            ]);
        });
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'message' => 'Mensagem sem assinatura.',
    ])->assertCreated();

    expect($providerText)->toBe('Mensagem sem assinatura.');
});

it('grava áudio do navegador como mensagem vinculada ao chamado', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $audio = UploadedFile::fake()->create('audio.webm', 12, 'audio/webm');

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'audio' => $audio,
    ])
        ->assertCreated()
        ->assertJsonPath('message.type', 'audio');

    $message = WhatsAppMessage::query()->where('type', 'audio')->firstOrFail();

    Storage::disk('public')->assertExists($message->attachment_path);
});

it('exclui mensagem de forma lógica na conversa do chamado', function () {
    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);
    $message = WhatsAppMessage::factory()->outbound()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $agent->id,
    ]);

    $this->deleteJson(route('agent.ticket.whatsapp.messages.destroy', [$ticket, $message]))
        ->assertOk()
        ->assertJsonPath('status', 'deleted');

    $message->refresh();

    expect($message->deleted_at)->not->toBeNull()
        ->and($message->deleted_by)->toBe($agent->id);
});

it('libera manualmente o bot para conversa do chamado do agente', function () {
    $agent = actingAsAgent();
    [, $conversation] = agent_whatsapp_ticket($agent);

    $this->post(route('agent.whatsapp.release', $conversation))
        ->assertRedirect()
        ->assertSessionHas('status');

    $conversation->refresh();

    expect($conversation->state)->toBe(ConversationState::GREETING)
        ->and($conversation->last_activity_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('bloqueia liberação manual do bot por agente sem permissão no chamado', function () {
    $owner = User::factory()->agent()->create();
    [, $conversation] = agent_whatsapp_ticket($owner);
    actingAsAgent();

    $this->post(route('agent.whatsapp.release', $conversation))
        ->assertForbidden();

    expect($conversation->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
});

it('pausa manualmente o bot para conversa do chamado do agente', function () {
    $agent = actingAsAgent();
    [, $conversation] = agent_whatsapp_ticket($agent);
    $conversation->update(['state' => ConversationState::GREETING]);

    $this->post(route('agent.whatsapp.pause', $conversation))
        ->assertRedirect()
        ->assertSessionHas('status');

    $conversation->refresh();

    expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
        ->and($conversation->last_activity_at->isAfter(now()->subMinute()))->toBeTrue();
});

it('bloqueia pausa manual do bot por agente sem permissão no chamado', function () {
    $owner = User::factory()->agent()->create();
    [, $conversation] = agent_whatsapp_ticket($owner);
    $conversation->update(['state' => ConversationState::GREETING]);
    actingAsAgent();

    $this->post(route('agent.whatsapp.pause', $conversation))
        ->assertForbidden();

    expect($conversation->fresh()->state)->toBe(ConversationState::GREETING);
});

it('serializa attachment_url apontando para a rota autenticada com disposition=inline', function () {
    // O bug: a URL pública (Storage::disk('public')->url) usa o host de APP_URL,
    // o que quebra o <img> quando o agente acessa o app por outro host. A
    // correção é roteamento autenticado — corrige o bug e fecha o IDOR latente
    // (URL pública dispensava auth/Policy).
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    $message = WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => 'image',
        'attachment_path' => 'whatsapp/attachments/imagem.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->getJson(route('agent.ticket.whatsapp.messages', $ticket))->assertOk();
    $attachmentUrl = $response->json('messages.0.attachment_url');

    expect($attachmentUrl)
        ->toBeString()
        ->and($attachmentUrl)->not->toContain('/storage/whatsapp/attachments/imagem.jpg')
        ->and($attachmentUrl)->toContain("/tickets/{$ticket->id}/whatsapp/messages/{$message->id}/download")
        ->and($attachmentUrl)->toContain('disposition=inline');
});

it('serve imagem inline quando ?disposition=inline é passado para a rota de download', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    Storage::disk('public')->put('whatsapp/attachments/foto.jpg', 'binário-fake');

    $message = WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => 'image',
        'direction' => 'inbound',
        'attachment_path' => 'whatsapp/attachments/foto.jpg',
        'original_filename' => 'foto-cliente.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->get(route('agent.ticket.whatsapp.messages.download', [
        'ticket' => $ticket,
        'message' => $message,
        'disposition' => 'inline',
    ]))->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('image/jpeg')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($response->headers->get('Content-Disposition'))->toContain('foto-cliente.jpg');
});

it('força attachment mesmo com disposition=inline quando mimetype não é image/audio/video (XSS via inline)', function () {
    // Defesa: um arquivo .html armazenado com mime text/html não deve ser
    // servido inline (executaria JS no domínio do app).
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    Storage::disk('public')->put('whatsapp/attachments/page.html', '<script>alert(1)</script>');

    $message = WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => 'document',
        'direction' => 'inbound',
        'attachment_path' => 'whatsapp/attachments/page.html',
        'original_filename' => 'page.html',
        'mime_type' => 'text/html',
    ]);

    $response = $this->get(route('agent.ticket.whatsapp.messages.download', [
        'ticket' => $ticket,
        'message' => $message,
        'disposition' => 'inline',
    ]))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment');
});

it('aceita áudios em formato ogg (compatível com Safari/Firefox)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $audio = UploadedFile::fake()->create('audio.ogg', 12, 'audio/ogg');

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'audio' => $audio,
    ])->assertCreated();
});

it('aceita áudios em formato m4a/mp4 (Safari iOS)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $audio = UploadedFile::fake()->create('audio.m4a', 12, 'audio/mp4');

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'audio' => $audio,
    ])->assertCreated();
});

it('aceita anexos de vídeo mp4', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('sendMedia')->once()->andReturn('msg_remote_123');
    app()->instance(WhatsAppService::class, $whatsApp);

    $video = UploadedFile::fake()->create('demo.mp4', 100, 'video/mp4');

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'attachment' => $video,
    ])
        ->assertCreated()
        ->assertJsonPath('message.type', 'video');
});

it('aceita anexos de documento (docx)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('sendMedia')->once()->andReturn('msg_doc_1');
    app()->instance(WhatsAppService::class, $whatsApp);

    $document = UploadedFile::fake()->create(
        'contrato.docx',
        20,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'attachment' => $document,
    ])
        ->assertCreated()
        ->assertJsonPath('message.type', 'document');
});

it('aceita arquivos técnicos arbitrários (.dll, .pfx, .exe, .bat)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $counter = 0;
    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('sendMedia')->andReturnUsing(function () use (&$counter): string {
        return 'msg_bin_'.(++$counter);
    });
    app()->instance(WhatsAppService::class, $whatsApp);

    foreach ([
        ['driver.dll', 'application/x-msdownload'],
        ['cert.pfx', 'application/x-pkcs12'],
        ['setup.exe', 'application/x-msdownload'],
        ['script.bat', 'application/x-bat'],
    ] as [$filename, $mime]) {
        $file = UploadedFile::fake()->create($filename, 10, $mime);

        $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
            'attachment' => $file,
        ])
            ->assertCreated()
            ->assertJsonPath('message.type', 'document');
    }
});

it('rejeita anexos acima de 20 MB', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $huge = UploadedFile::fake()->create('huge.bin', 20481, 'application/octet-stream');

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'attachment' => $huge,
    ])->assertStatus(422);
});

it('propaga o nome original do .pfx ao provider em vez de UUID.bin (Bug 2)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket] = agent_whatsapp_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('sendMedia')
        ->once()
        ->with(
            Mockery::type(\App\Models\WhatsApp\WhatsAppConversation::class),
            Mockery::type('string'),
            Mockery::any(), // caption
            'application/x-pkcs12',
            $agent->id,
            'cert-prod-2026.pfx', // 6º argumento: nome original preservado
        )
        ->andReturn('msg_pfx_1');
    app()->instance(WhatsAppService::class, $whatsApp);

    $cert = UploadedFile::fake()->create('cert-prod-2026.pfx', 8, 'application/x-pkcs12');

    $this->postJson(route('agent.ticket.whatsapp.messages.store', $ticket), [
        'attachment' => $cert,
    ])
        ->assertCreated()
        ->assertJsonPath('message.original_filename', 'cert-prod-2026.pfx');

    expect(\App\Models\WhatsApp\WhatsAppMessage::query()->latest('id')->first()->original_filename)
        ->toBe('cert-prod-2026.pfx');
});

it('disponibiliza rota de download que serve o anexo com nome e mime original (Bug 3)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    Storage::disk('public')->put('whatsapp/attachments/abc-123.xml', '<NFe></NFe>');

    $message = WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => 'document',
        'direction' => 'inbound',
        'attachment_path' => 'whatsapp/attachments/abc-123.xml',
        'original_filename' => 'NFe-12345.xml',
        'mime_type' => 'application/xml',
    ]);

    $response = $this->get(route('agent.ticket.whatsapp.messages.download', [
        'ticket' => $ticket,
        'message' => $message,
    ]))->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('application/xml')
        ->and($response->headers->get('Content-Disposition'))->toContain('NFe-12345.xml');
});

it('serializa download_url e original_filename junto com attachment_url', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => 'document',
        'direction' => 'inbound',
        'attachment_path' => 'whatsapp/attachments/abc-456.xml',
        'original_filename' => 'NFe-555.xml',
        'mime_type' => 'application/xml',
    ]);

    $response = $this->getJson(route('agent.ticket.whatsapp.messages', $ticket))->assertOk();

    expect($response->json('messages.0.original_filename'))->toBe('NFe-555.xml')
        ->and($response->json('messages.0.download_url'))->toContain("/tickets/{$ticket->id}/whatsapp/messages/");
});

it('bloqueia download de anexo de conversa de outro chamado (IDOR)', function () {
    Storage::fake('public');

    $agent = actingAsAgent();
    [$ticket, $conversation] = agent_whatsapp_ticket($agent);

    [$alheio] = agent_whatsapp_ticket($agent);

    $message = WhatsAppMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'type' => 'document',
        'direction' => 'inbound',
        'attachment_path' => 'whatsapp/attachments/x.xml',
        'mime_type' => 'application/xml',
    ]);

    $this->get(route('agent.ticket.whatsapp.messages.download', [
        'ticket' => $alheio,
        'message' => $message,
    ]))->assertNotFound();
});
