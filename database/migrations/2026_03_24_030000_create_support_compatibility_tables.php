<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
                $table->string('name', 150);
                $table->timestamps();

                $table->unique(['state_id', 'name']);
            });
        }

        if (! Schema::hasTable('service')) {
            Schema::create('service', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasTable('sales_order_status')) {
            Schema::create('sales_order_status', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasTable('sales_order')) {
            Schema::create('sales_order', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('service_id')->index();
                $table->unsignedBigInteger('status_id')->index();
                $table->dateTime('due_date')->index();
                $table->decimal('amount', 12, 2)->default(0);

                $table->unique(['customer_id', 'service_id', 'due_date'], 'sales_order_customer_service_due_unique');
            });
        }

        if (! Schema::hasTable('ticket_files')) {
            Schema::create('ticket_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('ticketit')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('extension', 20);
                $table->string('path', 255)->nullable();
                $table->index(['ticket_id', 'name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_files');
        Schema::dropIfExists('sales_order');
        Schema::dropIfExists('sales_order_status');
        Schema::dropIfExists('service');
        Schema::dropIfExists('cities');
    }
};
