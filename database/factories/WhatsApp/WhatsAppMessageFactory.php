<?php

namespace Database\Factories\WhatsApp;

use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhatsAppMessageFactory extends Factory
{
    protected $model = WhatsAppMessage::class;

    public function definition(): array
    {
        return [
            'conversation_id'     => WhatsAppConversation::factory(),
            'direction'           => 'inbound',
            'type'                => 'text',
            'body'                => fake()->sentence(),
            'attachment_path'     => null,
            'mime_type'           => null,
            'file_size'           => null,
            'provider_message_id' => fake()->uuid(),
        ];
    }

    public function inbound(): static
    {
        return $this->state(['direction' => 'inbound']);
    }

    public function outbound(): static
    {
        return $this->state(['direction' => 'outbound', 'provider_message_id' => null]);
    }

    public function image(): static
    {
        return $this->state([
            'type'            => 'image',
            'body'            => null,
            'attachment_path' => 'whatsapp/attachments/' . fake()->uuid() . '.jpg',
            'mime_type'       => 'image/jpeg',
            'file_size'       => fake()->numberBetween(10000, 500000),
        ]);
    }

    public function document(): static
    {
        return $this->state([
            'type'            => 'document',
            'body'            => null,
            'attachment_path' => 'whatsapp/attachments/' . fake()->uuid() . '.pdf',
            'mime_type'       => 'application/pdf',
            'file_size'       => fake()->numberBetween(50000, 2000000),
        ]);
    }
}
