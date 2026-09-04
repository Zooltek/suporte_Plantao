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
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('Razão Social');
                $table->string('cnpj')->nullable()->unique()->comment('CNPJ');
                $table->unsignedBigInteger('state_id')->nullable();
                $table->unsignedBigInteger('software_id')->nullable();
                $table->unsignedBigInteger('customer_group_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
