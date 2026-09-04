<?php

namespace App\Http\Resources\API\V1\Tickets;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'ticket_id'   => $this->ticket_id,
            'event'       => $this->event,
            'event_label' => $this->event_label,
            'operation'   => $this->operation,
            'field'       => $this->field,
            'field_label' => $this->field_label,
            'old_value'   => $this->old_value,
            'new_value'   => $this->new_value,
            'created_at'  => $this->created_at?->toDateTimeString(),
            'user'        => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
        ];
    }
}
