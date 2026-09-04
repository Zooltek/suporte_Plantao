<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id')->index();
            $table->foreign('conversation_id')
                ->references('id')
                ->on('whatsapp_conversations')
                ->cascadeOnDelete();

            // Direção: inbound (cliente → sistema) | outbound (sistema → cliente)
            $table->enum('direction', ['inbound', 'outbound']);

            // Tipo: text | image | document | audio | video
            $table->string('type', 20)->default('text');

            $table->text('body')->nullable();

            // Para mídias recebidas: caminho em storage/app/public
            $table->string('attachment_path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();

            // ID externo da mensagem no provedor (para idempotência)
            $table->string('provider_message_id')->nullable()->unique();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
