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
        Schema::dropIfExists('implantation');
        Schema::dropIfExists('implantation_record');
        Schema::dropIfExists('implantation_record_module');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('implantation', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('software_id');
            $table->unsignedBigInteger('agent_id');
            $table->dateTime('implantation_date');
            $table->timestamps();
        });

        Schema::create('implantation_record', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('implantation_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('contact');
            $table->string('obs');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->timestamps();
        });

        Schema::create('implantation_record_module', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }
};
