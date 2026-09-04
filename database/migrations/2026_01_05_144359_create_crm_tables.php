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
        Schema::create('crm_feedback', function (Blueprint $table) {
            $table->id();
            $table->longText('content')->nullable();
            $table->longText('suggestions')->nullable();
            $table->longText('complaint')->nullable();
            $table->longText('cancel_content')->nullable();
            $table->string('contact')->nullable();
            $table->string('version')->nullable();
            $table->string('release')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('fin');
            $table->timestamps();

            // Foreign keys opcionais:
            // $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('crm_feedback_element', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feedback_id');
            $table->unsignedBigInteger('element_id');
            $table->string('value');
        });

        Schema::create('crm_feedback_element_type', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('type');
            $table->string('data')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->unsignedTinyInteger('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_feedback');
        Schema::dropIfExists('crm_feedback_element');
        Schema::dropIfExists('crm_feedback_element_type');
    }
};
