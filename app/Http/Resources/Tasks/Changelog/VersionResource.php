<?php

namespace App\Http\Resources\Tasks\Changelog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'id'             => $this->id,
            'name'           => $this->name,
            'project_id'     => $this->project_id,
            'reference_date' => $this->reference_date?->format('d/m/Y H:i:s'),
            'changelogs'     => $this->whenLoaded('changelogs'),
        ];
    }
}
