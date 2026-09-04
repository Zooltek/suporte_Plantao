<?php

namespace App\Http\Resources\Schedule;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordResource extends JsonResource
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
            'start' => $this->start,
            'end' => $this->end,
            // lógica de "customer_name" centralizada no Resource
            'customer_name' => $this->customer?->trade_name 
                               ?? $this->schedule?->customer?->trade_name 
                               ?? 'N/A',
            'agent' => $this->whenLoaded('agent'),
            'module' => $this->whenLoaded('module'),
            'elements' => $this->whenLoaded('elements'),
            'schedule' => $this->whenLoaded('schedule'),
        ];
    }
}
