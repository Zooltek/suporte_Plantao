<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users');
            $table->unsignedBigInteger('ticket_id')->nullable()->index()
                  ->comment('Ticket de origem (se criado a partir de chamado)');
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('title');
            $table->text('problem');
            $table->text('solution');
            $table->string('tags')->nullable()->comment('Tags separadas por vírgula');
            $table->enum('visibility', ['internal', 'public'])->default('internal');
            $table->unsignedInteger('views')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_articles');
    }
};
