<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_schedule_module', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('schedule_module_id');

            $table->primary(['customer_id', 'schedule_module_id']);

            $table->foreign('customer_id')
                ->references('id')->on('customers')
                ->cascadeOnDelete();

            $table->foreign('schedule_module_id')
                ->references('id')->on('schedule_record_module')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_schedule_module');
    }
};
