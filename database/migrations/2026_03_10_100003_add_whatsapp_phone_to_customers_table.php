<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Número WhatsApp vinculado ao cliente para identificação automática
            // Formato E.164 sem "+": 5527999999999
            $table->string('whatsapp_phone', 20)
                ->nullable()
                ->after('telephone_2')
                ->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'whatsapp_phone')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_whatsapp_phone_index');
            $table->dropColumn('whatsapp_phone');
        });
    }
};
