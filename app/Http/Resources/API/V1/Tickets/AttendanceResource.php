<?php

namespace App\Http\Resources\API\V1\Tickets;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'ticket_id'        => $this->ticket_id,
            'user_id'          => $this->user_id,
            'notes'            => $this->notes,
            'return_zap'       => $this->return_zap,
            'return_tel'       => $this->return_tel,
            'return_cel'       => $this->return_cel,
            'return_assigned_to' => $this->return_assigned_to,
            'return_scheduled_at'=> $this->return_scheduled_at?->toDateTimeString(),
            'returned_by'      => $this->returned_by,
            'returned_at'      => $this->returned_at?->toDateTimeString(),
            'created_at'       => $this->created_at?->toDateTimeString(),
            'updated_at'       => $this->updated_at?->toDateTimeString(),
            'user'             => $this->whenLoaded('user'),
            'returned_by_user' => $this->whenLoaded('returnedBy'),
            'return_assignee'  => $this->whenLoaded('returnAssignee'),
        ];
    }
}
