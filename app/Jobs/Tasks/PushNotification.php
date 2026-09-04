<?php

namespace App\Jobs\Tasks;

use App\Models\Tasks\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que a job pode ser tentada.
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Notification $notification
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Puxa a URL do .env (TASK_APP_URL)
        $baseUrl = env('TASK_APP_URL', 'http://localhost');
        $endpoint = "{$baseUrl}/consuldata-panel/public/api/tasks/notifications";

        $response = Http::withoutVerifying()
            ->asForm()
            ->post($endpoint, [
                'ref_id'    => $this->notification->ref_id,
                'content'   => $this->notification->content,
                'kind'      => $this->notification->kind,
                'author_id' => $this->notification->author_id,
                'user_id'   => $this->notification->user_id,
                'icon'      => $this->notification->icon,
            ]);

        if ($response->failed()) {
            Log::alert("Falha ao enviar push: " . $response->status() . " - " . $response->body());
            $response->throw();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Job PushNotification falhou permanentemente: " . $exception->getMessage());
    }
}
