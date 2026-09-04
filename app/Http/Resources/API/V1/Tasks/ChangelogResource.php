<?php

namespace App\Http\Resources\API\V1\Tasks;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangelogResource extends JsonResource
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
            'user_id' => $this->user_id,
            'task_id' => $this->task_id ?? null,
            'project_id' => $this->project_id ?? null,
            'version_id' => $this->version_id,
            'content' => $this->content ?? $this->description ?? $this->title ?? null,
            'sort_order' => $this->sort_order ?? null,
            'blank' => $this->blank ?? false,
            'color' => $this->color ?? null,
            'bold' => $this->bold ?? false,
            'title' => isset($this->title) && is_bool($this->title) ? $this->title : ($this->title ?? null),
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'user' => $this->whenLoaded('user'),
        ];
    }
}
