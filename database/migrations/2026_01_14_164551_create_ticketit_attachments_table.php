<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ticketit_attachments')) {
            Schema::create('ticketit_attachments', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('mime'); // Tipo do arquivo (ex: image/jpeg, application/pdf)
                
                // Relacionamento com quem enviou (User)
                $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
                
                // Relacionamento com o Ticket
                $table->foreignId('ticket_id')->constrained('ticketit')->cascadeOnDelete();
                
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticketit_attachments');
    }
};
