<?php

namespace App\Http\Resources\API\V1\Tasks;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProjectResource extends JsonResource
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
            'project_id' => $this->project_id,
            'color' => $this->color,
            'project' => $this->whenLoaded('project'),
            'user' => $this->whenLoaded('user'),
        ];
    }
}
