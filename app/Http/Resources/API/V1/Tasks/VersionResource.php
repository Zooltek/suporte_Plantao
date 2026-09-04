<?php

namespace App\Http\Resources\API\V1\Tasks;

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
            'version' => $this->when(isset($this->version), fn() => $this->version),
            'environment' => $this->when(isset($this->environment), fn() => $this->environment),
            'timestamp' => $this->when(isset($this->timestamp), fn() => $this->timestamp),
        ];
    }
}
