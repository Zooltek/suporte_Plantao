<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'department_id'      => $this->department_id,
            'department_name'    => $this->department->name ?? 'Não atribuído',
            'last_tickets_count' => $this->last_tickets_count ?? 0,
        ];
    }
}