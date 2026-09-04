<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Customer
 */
class CustomerIntegrationResource extends JsonResource
{
    /**
     * Expõe apenas os campos relevantes para o financeiro (evita vazamento — OWASP A01).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'financeiro_id' => $this->financeiro_id,
            'name' => $this->name,
            'cnpj' => $this->cnpj,
            'email' => $this->email,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contacts' => $this->contacts
                ->where('origin', 'financeiro')
                ->values()
                ->map(static fn ($contact): array => [
                    'name' => $contact->name,
                    'email' => $contact->email,
                ]),
            'software_id' => $this->software_id,
            'is_active' => (bool) $this->is_active,
            'financial_irregular' => (bool) $this->financial_irregular,
        ];
    }
}
