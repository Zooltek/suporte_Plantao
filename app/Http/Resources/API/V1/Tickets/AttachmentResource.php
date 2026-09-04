<?php

namespace App\Http\Resources\API\V1\Tickets;

use App\Services\Ticket\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var AttachmentService $svc */
        $svc = app(AttachmentService::class);

        return [
            'id'            => $this->id,
            'ticket_id'     => $this->ticket_id,
            'name'          => $this->name,
            'original_name' => $this->original_name ?? $this->name,
            'mime'          => $this->mime,
            'size'          => $this->size ?? 0,
            'size_human'    => $this->humanSize(),
            'url'           => $svc->getPublicUrl($this->resource),
            'viewable'      => $svc->isViewableInBrowser($this->resource),
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }

    private function humanSize(): string
    {
        $bytes = $this->size ?? 0;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
