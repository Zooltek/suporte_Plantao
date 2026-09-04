<?php

namespace App\Jobs\Agent\Schedule;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Schedule $schedule
    ) {}

    public function handle(): void
    {
        try {
            if (!$this->schedule->customer) {
                Log::warning("Sincronização abortada: Schedule [{$this->schedule->id}] sem cliente.");
                return;
            }

            // Puxa a URL da config e monta o endpoint
            $baseUrl = config('services.plenus.url');
            $endpoint = "{$baseUrl}/panel/public/api/schedules";

            $response = Http::withoutVerifying()
                ->asForm()
                ->post($endpoint, [
                    'customer'    => $this->schedule->customer->toJson(),
                    'schedule_id' => $this->schedule->id,
                    'customer_id' => $this->schedule->customer_id,
                    'agent_id'    => $this->schedule->agent_id,
                    'module_id'   => $this->schedule->module_id,
                    'start_at'    => $this->schedule->start_at?->format('Y-m-d H:i'),
                    'contact'     => $this->schedule->contact,
                    'obs'         => $this->schedule->obs,
                    'status'      => $this->schedule->status,
                ]);

            if ($response->failed()) {
                Log::error("Falha na API Plenus [{$this->schedule->id}]: " . $response->status());
                
                // Força o retry do Job caso não seja erro do cliente (4xx)
                $response->throw();
            }

            Log::info("Schedule enviada com sucesso: ID [{$this->schedule->id}]");

        } catch (Throwable $e) {
            Log::error("Erro no Job SyncSchedule [{$this->schedule->id}]: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical("SyncSchedule falhou permanentemente ID [{$this->schedule->id}]: " . $exception->getMessage());
    }
}
