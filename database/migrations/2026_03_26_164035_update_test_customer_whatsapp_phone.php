<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')
            ->where('cnpj', '12.345.678/0001-99')
            ->update([
                'whatsapp_phone' => '5527988213355',
            ]);
    }

    public function down(): void
    {
        DB::table('customers')
            ->where('cnpj', '12.345.678/0001-99')
            ->where('whatsapp_phone', '5527988213355')
            ->update([
                'whatsapp_phone' => null,
            ]);
    }
};
