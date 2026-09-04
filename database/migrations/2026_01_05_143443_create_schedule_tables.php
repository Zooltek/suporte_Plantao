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
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('module_id');
            $table->dateTime('start_at');
            $table->string('contact');
            $table->string('obs');
            $table->string('status', 4)->default('sch');
            $table->timestamps();
        });

        Schema::create('schedule_record', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('contact');
            $table->string('obs')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->dateTime('interval_start')->nullable();
            $table->dateTime('interval_end')->nullable();
            $table->string('version')->nullable();
            $table->string('release')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_record_module', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('schedule_record_element', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('element_id');
            $table->string('value');
        });

        Schema::create('schedule_record_element_type', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('type');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('software_id');
        });

        Schema::create('schedule_record_element_group', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule');
        Schema::dropIfExists('schedule_record');
        Schema::dropIfExists('schedule_record_module');
        Schema::dropIfExists('schedule_record_element');
        Schema::dropIfExists('schedule_record_element_type');
        Schema::dropIfExists('schedule_record_element_group');
    }
};
