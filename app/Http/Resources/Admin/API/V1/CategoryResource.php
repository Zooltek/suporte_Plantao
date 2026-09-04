<?php

namespace App\Http\Resources\Admin\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->category_id,
            'category_id' => $this->category_id,
            'name' => $this->description?->name,
            'permalink' => $this->description?->permalink,
            'description_text' => $this->description?->description,
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityLabel(),
            'priority_color' => $this->getPriorityColor(),
            'parent_id' => $this->parent_id,
            'department_id' => $this->department_id,
            'department_name' => $this->whenLoaded('department', fn () => $this->department?->name),
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
