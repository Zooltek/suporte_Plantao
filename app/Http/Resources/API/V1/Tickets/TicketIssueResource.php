<?php

namespace App\Http\Resources\API\V1\Tickets;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'ticket_id'   => $this->ticket_id,
            'title'       => $this->title,
            'description' => $this->description,
            'solution'    => $this->solution,
            'status'      => $this->status,
            'is_resolved' => $this->isResolved(),
            'resolved_at' => $this->resolved_at?->toDateTimeString(),
            'created_at'  => $this->created_at?->toDateTimeString(),
            'creator'     => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'resolver'    => $this->whenLoaded('resolver', fn () => $this->resolver ? [
                'id'   => $this->resolver->id,
                'name' => $this->resolver->name,
            ] : null),
        ];
    }
}
