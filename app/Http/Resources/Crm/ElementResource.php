<?php

namespace App\Http\Resources\Crm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'label'      => $this->label, // Supondo que exista este campo
            'sort_order' => $this->sort_order,
            'form_id'    => $this->form_id,
            // Adicione outros campos necessários aqui
        ];
    }
}
