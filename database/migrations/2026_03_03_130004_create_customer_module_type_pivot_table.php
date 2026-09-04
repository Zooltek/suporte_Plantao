<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_module_type', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('module_type_id')->constrained('company_module_types')->cascadeOnDelete();
            $table->primary(['customer_id', 'module_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_module_type');
    }
};
