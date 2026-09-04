<?php

namespace App\Http\Resources\API\V1\Tasks;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'classification' => $this->classification,
            'user_id' => $this->user_id,
            'author_id' => $this->author_id,
            'tester_id' => $this->tester_id,
            'project_id' => $this->project_id,
            'customer_id' => $this->customer_id,
            'software_id' => $this->software_id,
            'parent_id' => $this->parent_id,
            'delivery_at' => $this->delivery_at?->toDateTimeString(),
            'request_at' => $this->request_at?->toDateTimeString(),
            'started_at' => $this->started_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'labels' => $this->whenLoaded('labels', fn () =>
                $this->labels->map(fn ($label) => [
                    'id'   => $label->id,
                    'name' => $label->name,
                ])
            ),
            'software' => $this->whenLoaded('software', fn () => [
                'id'   => $this->software->id,
                'name' => $this->software->name,
            ]),
            'author' => $this->whenLoaded('author'),
            'tester' => $this->whenLoaded('tester'),
            'user' => $this->whenLoaded('user'),
            'customer' => $this->whenLoaded('customer'),
            'customer_software' => $this->when($this->customer?->software, function () {
                return $this->customer->software->name;
            }),
            'attachments' => $this->whenLoaded('attachments'),
            'project' => $this->whenLoaded('project'),
            'parent' => $this->whenLoaded('parent'),
            'subtasksCount' => $this->when(isset($this->subtasksCount), fn() => $this->subtasksCount),
        ];
    }
}
