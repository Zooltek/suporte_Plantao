<?php

namespace App\Http\Resources\Admin\API\V1\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementTypeResource extends JsonResource
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
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'data' => $this->data,
            'form_id' => $this->form_id,
            'sort_order' => $this->sort_order,
            'elements' => $this->whenLoaded('elements'),
        ];
    }
}