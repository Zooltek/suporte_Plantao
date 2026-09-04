<?php

namespace App\Console\Commands;

use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\EvolutionInstanceService;
use App\Support\Phone\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class WhatsAppHomologacaoCommand extends Command
{
    protected $signature = 'whatsapp:homologacao
        {--phone= : Número alvo em formato E.164 (ex: 5527999990000)}
        {--scenario=chatbot-start : Cenário de homologação disponível}
        {--webhook-url=http://suporte12_app:8080/api/webhook/whatsapp : Webhook interno da aplicação}
        {--timeout=20 : Timeout máximo por etapa, em segundos}
        {--poll-ms=500 : Intervalo de polling entre verificações, em milissegundos}
        {--allow-disconnected : Executa mesmo sem a instância open, validando apenas o fluxo interno}';

    protected $description = 'Executa uma homologação operacional do chatbot WhatsApp via webhook + fila + outbound';

    private string $runToken;

    public function handle(EvolutionInstanceService $evolution): int
    {
        $this->runToken = now()->format('YmdHis');

        $this->renderHeader();

        if (! $this->validateBaseConfiguration()) {
            return SymfonyCommand::FAILURE;
        }

        $scenario = (string) $this->option('scenario');

        if (! in_array($scenario, ['chatbot-start', 'menu-recovery'], true)) {
            $this->error("Cenário não suportado: {$scenario}. Use --scenario=chatbot-start ou --scenario=menu-recovery.");
            return SymfonyCommand::FAILURE;
        }

        $phone = $this->resolvePhone();

        if ($phone === null) {
            return SymfonyCommand::FAILURE;
        }

        if (! $this->validateConnectionState($evolution)) {
            return SymfonyCommand::FAILURE;
        }

        $existingConversation = $this->latestConversation($phone);
        $steps = $this->resolveSteps($existingConversation);

        if ($steps === null) {
            return SymfonyCommand::FAILURE;
        }

        $this->info("Telefone alvo: +{$phone}");
        $this->line('Webhook interno: ' . (string) $this->option('webhook-url'));
        $this->line("Cenário: {$scenario}");
        $this->newLine();

        foreach ($steps as $index => $step) {
            if (! $this->runStep($phone, $index + 1, $step)) {
                return SymfonyCommand::FAILURE;
            }
        }

        $this->renderSummary($phone);

        return SymfonyCommand::SUCCESS;
    }

    private function validateBaseConfiguration(): bool
    {
        if (config('whatsapp.provider') !== 'evolution') {
            $this->error('WHATSAPP_PROVIDER precisa ser "evolution" para a homologação operacional.');
            return false;
        }

        if (! config('whatsapp.enabled')) {
            $this->error('WHATSAPP_ENABLED precisa estar ativo para validar o outbound real da aplicação.');
            return false;
        }

        foreach ([
            'whatsapp.api_url' => 'WHATSAPP_API_URL',
            'whatsapp.evolution_instance' => 'WHATSAPP_EVOLUTION_INSTANCE',
            'whatsapp.evolution_api_key' => 'WHATSAPP_EVOLUTION_API_KEY',
        ] as $configKey => $label) {
            if (blank(config($configKey))) {
                $this->error("{$label} não está configurado.");
                return false;
            }
        }

        return true;
    }

    private function resolvePhone(): ?string
    {
        $phone = PhoneNumber::normalize((string) $this->option('phone'));

        if ($phone !== '') {
            return $phone;
        }

        $localNumbers = array_values(array_filter(array_map(
            static fn ($number): string => PhoneNumber::normalize((string) $number),
            config('whatsapp.local_test_numbers', [])
        )));

        if (app()->environment('local', 'testing') && $localNumbers !== []) {
            $defaultPhone = $localNumbers[0];
            $available = collect($localNumbers)
                ->map(static fn (string $number): string => '+' . $number)
                ->implode(', ');

            $this->warn(
                "Nenhum telefone informado. Usando número local padrão +{$defaultPhone}. "
                . "Disponíveis: {$available}"
            );

            return $defaultPhone;
        }

        if (! $this->option('allow-disconnected')) {
            $this->error('Informe --phone=5527999990000 para homologação com entrega real no telefone.');
            return null;
        }

        $generated = '5527' . random_int(900000000, 999999999);

        $this->warn("Nenhum telefone informado. Usando número sintético +{$generated} em modo allow-disconnected.");

        return $generated;
    }

    private function validateConnectionState(EvolutionInstanceService $evolution): bool
    {
        $state = (string) ($evolution->connectionState()['state'] ?? 'unknown');

        if ($state === 'open') {
            $this->info('Instância Evolution conectada (state: open).');
            return true;
        }

        if ($this->option('allow-disconnected')) {
            $this->warn("Instância Evolution não está open (state: {$state}). Prosseguindo apenas com validação interna.");
            return true;
        }

        $this->error("Instância Evolution não está conectada (state: {$state}).");
        $this->line('Escaneie o QR Code no painel Admin/Evolution Manager e execute novamente.');

        return false;
    }

    private function resolveSteps(?WhatsAppConversation $conversation): ?array
    {
        $menuMessage = config('whatsapp.messages.menu');
        $invalidOptionMessage = config('whatsapp.messages.invalid_option');

        if (! is_string($menuMessage) || ! is_string($invalidOptionMessage)) {
            $this->error('As mensagens do chatbot não estão configuradas corretamente.');
            return null;
        }

        if ($conversation === null || $conversation->state->isTerminal() || $conversation->isExpired()) {
            return [
                $this->chatbotStartStep(),
            ];
        }

        return match ($conversation->state) {
            ConversationState::GREETING => [
                $this->chatbotStartStep(),
            ],
            ConversationState::AWAITING_MENU => [
                $this->menuReplayStep($menuMessage, $invalidOptionMessage),
            ],
            ConversationState::HUMAN_PENDING => $this->failForHumanPending(),
            default => $this->failForActiveConversation($conversation),
        };
    }

    private function chatbotStartStep(): array
    {
        return [
            'label' => 'Entrada inicial do chatbot',
            'body' => 'oi',
            'expected_state' => ConversationState::AWAITING_COMPANY_CNPJ,
            'expected_fragments' => [config('whatsapp.messages.greeting_identified')],
        ];
    }

    private function menuReplayStep(string $menuMessage, string $invalidOptionMessage): array
    {
        return [
            'label' => 'Reentrada sem opção válida',
            'body' => 'oi',
            'expected_state' => ConversationState::AWAITING_MENU,
            'expected_fragments' => [$invalidOptionMessage, $menuMessage],
        ];
    }

    private function failForHumanPending(): ?array
    {
        $this->error('O telefone informado está em HUMAN_PENDING. Libere o bot no painel antes da homologação.');

        return null;
    }

    private function failForActiveConversation(WhatsAppConversation $conversation): ?array
    {
        $this->error(
            'O telefone informado já possui uma conversa ativa em andamento no estado "'
            . $conversation->state->value
            . '". Use outro telefone ou conclua essa conversa antes da homologação.'
        );

        return null;
    }

    private function runStep(string $phone, int $sequence, array $step): bool
    {
        $beforeConversation = $this->latestConversation($phone);
        $beforeConversationId = $beforeConversation?->id;
        $beforeOutboundCount = $beforeConversation?->messages()
            ->where('direction', 'outbound')
            ->count() ?? 0;

        $this->line("[{$sequence}] {$step['label']} -> \"{$step['body']}\"");

        $response = Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => (string) config('whatsapp.evolution_api_key'),
            ])
            ->post((string) $this->option('webhook-url'), $this->makePayload($phone, (string) $step['body'], $sequence));

        if (! $response->successful() || $response->json('status') !== 'queued') {
            $this->error('Falha ao enfileirar webhook: HTTP ' . $response->status());
            $this->line((string) $response->body());
            return false;
        }

        $conversation = $this->awaitExpectedUpdate(
            phone: $phone,
            beforeConversationId: $beforeConversationId,
            beforeOutboundCount: $beforeOutboundCount,
            expectedState: $step['expected_state'],
            expectedFragments: $step['expected_fragments'],
            timeoutSeconds: max(1, (int) $this->option('timeout')),
            pollMilliseconds: max(100, (int) $this->option('poll-ms')),
        );

        if (! $conversation) {
            $this->error("Timeout aguardando a etapa \"{$step['label']}\".");
            return false;
        }

        $lastOutbound = $conversation->messages()
            ->where('direction', 'outbound')
            ->latest('id')
            ->first();

        $this->info(
            "OK -> estado={$conversation->state->value}, outbound="
            . $conversation->messages()->where('direction', 'outbound')->count()
        );

        if ($lastOutbound instanceof WhatsAppMessage) {
            $this->line('Última resposta: ' . $this->preview($lastOutbound->body));
        }

        $this->newLine();

        return true;
    }

    private function awaitExpectedUpdate(
        string $phone,
        ?int $beforeConversationId,
        int $beforeOutboundCount,
        ConversationState $expectedState,
        array $expectedFragments,
        int $timeoutSeconds,
        int $pollMilliseconds,
    ): ?WhatsAppConversation {
        $deadline = now()->addSeconds($timeoutSeconds);

        while (now()->lt($deadline)) {
            $conversation = $this->latestConversation($phone);

            if ($conversation instanceof WhatsAppConversation && $conversation->state === $expectedState) {
                $outboundMessages = $conversation->messages()
                    ->where('direction', 'outbound')
                    ->orderByDesc('id')
                    ->get();

                $requiredOutboundCount = $conversation->id === $beforeConversationId
                    ? $beforeOutboundCount + 1
                    : 1;

                $lastOutboundBody = (string) $outboundMessages->first()?->body;
                $containsAllFragments = collect($expectedFragments)
                    ->every(fn (string $fragment): bool => str_contains($lastOutboundBody, $fragment));

                if ($outboundMessages->count() >= $requiredOutboundCount && $containsAllFragments) {
                    return $conversation;
                }
            }

            usleep($pollMilliseconds * 1000);
        }

        return null;
    }

    private function latestConversation(string $phone): ?WhatsAppConversation
    {
        return WhatsAppConversation::query()
            ->where('phone', $phone)
            ->latest()
            ->first();
    }

    private function makePayload(string $phone, string $body, int $sequence): array
    {
        return [
            'event' => 'messages.upsert',
            'instance' => (string) config('whatsapp.evolution_instance'),
            'data' => [
                'key' => [
                    'remoteJid' => "{$phone}@s.whatsapp.net",
                    'fromMe' => false,
                    'id' => "HOMOLOG-{$this->runToken}-{$sequence}",
                ],
                'message' => [
                    'conversation' => $body,
                ],
                'messageType' => 'conversation',
                'messageTimestamp' => time(),
            ],
        ];
    }

    private function renderSummary(string $phone): void
    {
        $conversation = $this->latestConversation($phone);

        $this->line('==============================================');
        $this->line('Resumo da homologação');
        $this->line('==============================================');

        if (! $conversation) {
            $this->warn('Nenhuma conversa encontrada ao final da homologação.');
            return;
        }

        $messages = $conversation->messages()->orderBy('id')->get();
        $inboundCount = $messages->where('direction', 'inbound')->count();
        $outboundCount = $messages->where('direction', 'outbound')->count();

        $this->info("Conversa #{$conversation->id}");
        $this->line("Estado final: {$conversation->state->value}");
        $this->line("Inbound: {$inboundCount}");
        $this->line("Outbound: {$outboundCount}");

        if ($this->option('phone')) {
            $this->line("Confirme no telefone +{$phone} se as mensagens do bot foram recebidas.");
        } else {
            $this->warn('Sem telefone real informado: a validação acima cobre webhook, fila, banco e geração de outbound.');
        }

        $this->newLine();
        $this->line('Histórico recente:');

        foreach ($messages->take(-4) as $message) {
            $direction = $message->direction === 'inbound' ? 'USER' : 'BOT ';
            $this->line(" - {$direction}: " . $this->preview((string) $message->body));
        }
    }

    private function preview(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', trim($message)) ?? '';

        return mb_strimwidth($message, 0, 120, '...');
    }

    private function renderHeader(): void
    {
        $this->newLine();
        $this->line('==============================================');
        $this->line('  WhatsApp Chatbot — Homologação');
        $this->line('==============================================');
        $this->newLine();
    }
}
