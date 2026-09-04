<?php

namespace App\Http\Resources\API\V1\Schedule;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'title' => $this->display_title,
            'kind' => $this->kind,
            'customer_id' => $this->customer_id,
            'ticket_id' => $this->ticket_id,
            'agent_id' => $this->agent_id,
            'module_id' => $this->module_id,
            'contact' => $this->contact,
            'obs' => $this->obs,
            'status' => $this->status,
            'requires_admin_confirmation' => $this->requires_admin_confirmation,
            'start_at' => $this->start_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'customer' => $this->whenLoaded('customer'),
            'agent' => $this->whenLoaded('agent'),
            'module' => $this->whenLoaded('module'),
            'records' => $this->whenLoaded('records'),
        ];
    }
}
