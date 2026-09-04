<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class WhatsAppDiagnosticoCommand extends Command
{
    private const WEBHOOK_EVENTS = ['MESSAGES_UPSERT', 'MESSAGES_SET'];

    protected $signature = 'whatsapp:diagnostico';

    protected $description = 'Verifica todos os componentes do chatbot WhatsApp e reporta problemas';

    private int $errors = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('==============================================');
        $this->line('  WhatsApp Chatbot — Diagnóstico');
        $this->line('==============================================');
        $this->newLine();

        $this->checkConfig();
        $this->checkRedis();
        $this->checkQueueWorker();
        $this->checkEvolutionApi();
        $this->checkConnectionState();
        $this->checkWebhook();

        $this->newLine();
        $this->line('==============================================');

        if ($this->errors === 0) {
            $this->info('  Tudo OK! O chatbot está pronto para uso.');
            $this->newLine();
            $this->line('  Envie uma mensagem de outro número para o');
            $this->line('  WhatsApp conectado e o chatbot irá responder.');
        } else {
            $this->error("  {$this->errors} problema(s) encontrado(s).");
            $this->newLine();
            $this->line('  Corrija os problemas acima e execute novamente:');
            $this->line('  php artisan whatsapp:diagnostico');
        }

        $this->line('==============================================');
        $this->newLine();

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checkConfig(): void
    {
        $this->info('1/6 — Verificando configuração .env...');

        $keys = [
            'WHATSAPP_ENABLED'              => config('whatsapp.enabled'),
            'WHATSAPP_PROVIDER'             => config('whatsapp.provider'),
            'WHATSAPP_API_URL'              => config('whatsapp.api_url'),
            'WHATSAPP_EVOLUTION_INSTANCE'   => config('whatsapp.evolution_instance'),
            'WHATSAPP_EVOLUTION_API_KEY'    => config('whatsapp.evolution_api_key'),
        ];

        foreach ($keys as $key => $value) {
            if (empty($value)) {
                $this->printFail("  {$key} não está definido no .env");
            } else {
                $display = $key === 'WHATSAPP_EVOLUTION_API_KEY'
                    ? substr((string) $value, 0, 8) . '***'
                    : $value;
                $this->printOk("  {$key} = {$display}");
            }
        }

        if (! config('whatsapp.enabled')) {
            $this->printFail('  WHATSAPP_ENABLED está desabilitado (false)');
        }

        $this->newLine();
    }

    private function checkRedis(): void
    {
        $this->info('2/6 — Verificando Redis...');

        try {
            $pong = Redis::ping();
            $this->printOk('  Redis respondendo');
        } catch (\Throwable $e) {
            $this->printFail("  Redis inacessível: {$e->getMessage()}");
        }

        $this->newLine();
    }

    private function checkQueueWorker(): void
    {
        $this->info('3/6 — Verificando Queue Worker...');

        $queueConnection = config('queue.default');
        $this->printOk("  Queue connection: {$queueConnection}");

        try {
            $size = app('queue')->connection($queueConnection)->size();
            $this->printOk("  Jobs pendentes na fila: {$size}");
        } catch (\Throwable $e) {
            $this->printWarn("  Não foi possível verificar a fila: {$e->getMessage()}");
        }

        // Verifica se o container worker está rodando via exec
        $workerCheck = @shell_exec("pgrep -f 'artisan queue:work' 2>/dev/null");
        if (! empty(trim((string) $workerCheck))) {
            $this->printOk('  Processo queue:work detectado');
        } else {
            $this->printWarn('  Processo queue:work não detectado neste container');
            $this->line('    Se o worker roda em container separado (suporte12_worker), ignore este aviso.');
        }

        $this->newLine();
    }

    private function checkEvolutionApi(): void
    {
        $this->info('4/6 — Verificando Evolution API...');

        $apiUrl = config('whatsapp.api_url');

        if (empty($apiUrl)) {
            $this->printFail('  WHATSAPP_API_URL não configurada');
            $this->newLine();
            return;
        }

        try {
            $response = Http::timeout(10)->get($apiUrl);

            if ($response->successful() && str_contains($response->body(), 'Evolution API')) {
                $version = $response->json('version', '?');
                $this->printOk("  Evolution API acessível (v{$version})");
            } else {
                $this->printFail("  Evolution API respondeu com status {$response->status()}");
            }
        } catch (\Throwable $e) {
            $this->printFail("  Evolution API inacessível: {$e->getMessage()}");
        }

        $this->newLine();
    }

    private function checkConnectionState(): void
    {
        $this->info('5/6 — Verificando conexão da instância WhatsApp...');

        $apiUrl  = config('whatsapp.api_url');
        $apiKey  = config('whatsapp.evolution_api_key');
        $instance = config('whatsapp.evolution_instance');

        if (empty($apiUrl) || empty($apiKey) || empty($instance)) {
            $this->printWarn('  Configuração incompleta, pulando...');
            $this->newLine();
            return;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['apikey' => $apiKey])
                ->get("{$apiUrl}/instance/connectionState/{$instance}");

            $state = $response->json('instance.state', 'unknown');

            if ($state === 'open') {
                $this->printOk('  WhatsApp conectado (state: open)');
            } else {
                $label = match ($state) {
                    'connecting' => 'tentando conectar',
                    'close'      => 'DESCONECTADO',
                    default      => 'desconhecido',
                };
                $this->printFail("  WhatsApp {$label} (state: {$state})");
                $this->newLine();
                $this->printConnectionInstructions();
            }
        } catch (\Throwable $e) {
            $this->printFail("  Erro ao consultar estado: {$e->getMessage()}");
        }

        $this->newLine();
    }

    private function checkWebhook(): void
    {
        $this->info('6/6 — Verificando webhook...');

        $apiUrl   = config('whatsapp.api_url');
        $apiKey   = config('whatsapp.evolution_api_key');
        $instance = config('whatsapp.evolution_instance');

        if (empty($apiUrl) || empty($apiKey) || empty($instance)) {
            $this->printWarn('  Configuração incompleta, pulando...');
            $this->newLine();
            return;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['apikey' => $apiKey])
                ->get("{$apiUrl}/webhook/find/{$instance}");

            $url     = $response->json('url', '');
            $enabled = $response->json('enabled', false);

            $events = $response->json('events', []);
            $webhookUrl = $this->expectedWebhookUrl();
            $matchesExpectedConfig = $url === $webhookUrl
                && $this->webhookEventsMatch(is_array($events) ? $events : []);

            if (! empty($url) && $enabled && $matchesExpectedConfig) {
                $this->printOk("  Webhook ativo: {$url}");

                // Verifica se a URL aponta para o app correto
                if (! str_contains($url, '/api/webhook/whatsapp')) {
                    $this->printWarn("  URL do webhook não contém /api/webhook/whatsapp");
                }
            } else {
                $this->printFail('  Webhook não configurado, desabilitado ou divergente');
                $this->line('    Tentando configurar automaticamente...');

                Http::timeout(15)
                    ->withHeaders(['apikey' => $apiKey, 'Content-Type' => 'application/json'])
                    ->post("{$apiUrl}/webhook/set/{$instance}", [
                        'webhook' => [
                            'enabled'  => true,
                            'url'      => $webhookUrl,
                            'headers'  => ['apikey' => $apiKey],
                            'byEvents' => false,
                            'base64'   => false,
                            'events'   => self::WEBHOOK_EVENTS,
                        ],
                    ]);

                $this->printOk("  Webhook configurado: {$webhookUrl}");
            }
        } catch (\Throwable $e) {
            $this->printFail("  Erro ao verificar webhook: {$e->getMessage()}");
        }

        $this->newLine();
    }

    private function expectedWebhookUrl(): string
    {
        $configuredUrl = trim((string) config('whatsapp.webhook_url', ''));

        return $configuredUrl !== ''
            ? $configuredUrl
            : 'http://suporte12_app:8080/api/webhook/whatsapp';
    }

    private function webhookEventsMatch(array $events): bool
    {
        return empty(array_diff(self::WEBHOOK_EVENTS, $events));
    }

    private function printOk(string $message): void
    {
        $this->line("  <fg=green>[OK]</> {$message}");
    }

    private function printFail(string $message): void
    {
        $this->line("  <fg=red>[FALHA]</> {$message}");
        $this->errors++;
    }

    private function printWarn(string $message): void
    {
        $this->line("  <fg=yellow>[AVISO]</> {$message}");
    }

    private function printConnectionInstructions(): void
    {
        $this->line('  ┌─────────────────────────────────────────────────┐');
        $this->line('  │  AÇÃO NECESSÁRIA: Escanear QR Code              │');
        $this->line('  │                                                  │');
        $this->line('  │  Opção 1: Painel Admin do Sistema               │');
        $this->line('  │    Acesse: <url-do-sistema>/admin/whatsapp      │');
        $this->line('  │    Clique em "Gerar QR Code" e escaneie         │');
        $this->line('  │                                                  │');
        $this->line('  │  Opção 2: Manager da Evolution API              │');
        $this->line('  │    Acesse: <url-evolution>/manager              │');
        $this->line('  │    Clique na instância e escaneie o QR Code     │');
        $this->line('  └─────────────────────────────────────────────────┘');
    }
}
