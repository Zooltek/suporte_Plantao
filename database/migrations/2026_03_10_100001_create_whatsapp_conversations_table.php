<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();

            // Número do cliente (formato E.164 sem o "+": 5527999999999)
            $table->string('phone', 20)->index();

            // Estado atual do chatbot (enum gerenciado em App\Enums\WhatsApp\ConversationState)
            $table->string('state', 50)->default('greeting');

            // Dados coletados durante o fluxo (nome, empresa, área, problema, anexos)
            $table->json('payload')->nullable();

            // Empresa identificada na base de clientes (nullable: cliente não encontrado)
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Ticket gerado ao final do fluxo
            $table->unsignedBigInteger('ticket_id')->nullable()->index();

            // Controle de sessão
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
