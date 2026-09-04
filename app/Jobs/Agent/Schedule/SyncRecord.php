<?php
namespace App\Jobs\Agent\Schedule;

use App\Models\Schedule\Record;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 60;

    public function __construct(public Record $record) {}

    public function handle(): void
    {
        if ($this->record->legacy_id !== null) {
            return;
        }

        $customer = $this->record->customer ?? $this->record->schedule?->customer;

        if (!$customer) {
            Log::error("Impossível sincronizar Record [{$this->record->id}]: Cliente não encontrado.");
            return;
        }

        $this->record->load('elements');

        // Usando throw() para que falhas de rede acionem o retry do Laravel
        $response = Http::withoutVerifying()
            ->asForm()
            ->post('https://sistemaplenus.com.br/panel/public/api/records', [
                'legacy_id'      => $this->record->id,
                'customer'       => $customer->toJson(),
                'schedule_id'    => $this->record->schedule_id,
                'customer_id'    => $customer->customer_id ?? $customer->id,
                'agent_id'       => $this->record->agent_id,
                'module_id'      => $this->record->module_id,
                'start_hour'     => $this->record->start?->format('H:i'),
                'end_hour'       => $this->record->end?->format('H:i'),
                'interval_start' => $this->record->interval_start?->format('H:i'),
                'interval_end'   => $this->record->interval_end?->format('H:i'),
                'date'           => $this->record->start?->format('Y-m-d'),
                'version'        => $this->record->version,
                'release'        => $this->record->release,
                'contact'        => $this->record->contact,
                'obs'            => $this->record->obs,
                'status'         => $this->record->status,
                'elements'       => $this->record->elements->toJson(),
            ]);

        if ($response->failed()) {
            Log::error("Erro na API ao sincronizar Record [{$this->record->id}]: " . $response->body());
            $response->throw(); // Isso faz o Job tentar novamente (Retry)
        }

        Log::info("Record enviado com sucesso: ID [{$this->record->id}]");
    }

    public function failed(Throwable $exception): void
    {
        Log::critical("SyncRecord falhou permanentemente: " . $exception->getMessage());
    }
}
