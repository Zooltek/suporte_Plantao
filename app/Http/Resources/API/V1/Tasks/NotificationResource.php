<?php

namespace App\Http\Resources\API\V1\Tasks;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
            'ref_id' => $this->ref_id ?? null,
            'content' => $this->content ?? $this->message,
            'seen' => $this->seen ?? 0,
            'status' => $this->status,
            'icon' => $this->when(isset($this->icon), fn() => $this->icon),
            'url' => $this->when(isset($this->url), fn() => $this->url),
            'timestamp' => $this->when(isset($this->timestamp), fn() => $this->timestamp),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'task' => $this->whenLoaded('task'),
        ];
    }
}
