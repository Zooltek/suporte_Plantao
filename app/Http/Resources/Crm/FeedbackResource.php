<?php

namespace App\Http\Resources\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackResource extends JsonResource
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
            'status_key' => $this->status,
            'completed_at' => $this->completed_at?->format('d/m/Y H:i:s'),
            'created_at' => $this->created_at?->format('d/m/Y H:i:s'),
            
            // relacionamentos carregados apenas se presentes (quandoRequested)
            'customer' => $this->whenLoaded('customer'),
            'origin' => $this->whenLoaded('origin'),
            'status_details' => $this->whenLoaded('status'),
            'elements' => ElementResource::collection($this->whenLoaded('elements')),
            'agent' => $this->whenLoaded('agent'),
        ];
    }
}
