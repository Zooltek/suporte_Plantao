<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->change();
            $table->string('email', 255)->nullable()->after('phone');
            $table->string('origin', 20)->default('support')->after('email');
            $table->index(['customer_id', 'origin']);
        });

        DB::table('customers')
            ->where(function ($query) {
                $query->whereNotNull('contact_name')
                    ->where('contact_name', '<>', '')
                    ->orWhere(function ($query) {
                        $query->whereNotNull('contact_email')
                            ->where('contact_email', '<>', '');
                    });
            })
            ->orderBy('id')
            ->chunkById(200, function ($customers): void {
                $now = now();
                $rows = $customers->map(static fn ($customer): array => [
                    'customer_id' => $customer->id,
                    'name' => $customer->contact_name ?: $customer->contact_email,
                    'phone' => null,
                    'email' => $customer->contact_email ?: null,
                    'origin' => 'financeiro',
                    'is_main' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('customer_contacts')->insert($rows);
            });
    }

    public function down(): void
    {
        DB::table('customer_contacts')->where('origin', 'financeiro')->delete();

        Schema::table('customer_contacts', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'origin']);
            $table->dropColumn(['email', 'origin']);
            $table->string('phone', 30)->nullable(false)->change();
        });
    }
};
