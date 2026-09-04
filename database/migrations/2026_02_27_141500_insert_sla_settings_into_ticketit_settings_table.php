<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $settings = [
            // SLA Baixa
            ['slug' => 'sla_low_attention', 'value' => '30', 'default' => '30', 'lang' => 'ticketit::admin.sla-low-attention'],
            ['slug' => 'sla_low_warning',   'value' => '60', 'default' => '60', 'lang' => 'ticketit::admin.sla-low-warning'],
            ['slug' => 'sla_low_critical',  'value' => '90', 'default' => '90', 'lang' => 'ticketit::admin.sla-low-critical'],
            // SLA Media
            ['slug' => 'sla_med_attention', 'value' => '20', 'default' => '20', 'lang' => 'ticketit::admin.sla-med-attention'],
            ['slug' => 'sla_med_warning',   'value' => '40', 'default' => '40', 'lang' => 'ticketit::admin.sla-med-warning'],
            ['slug' => 'sla_med_critical',  'value' => '60', 'default' => '60', 'lang' => 'ticketit::admin.sla-med-critical'],
            // SLA Alta
            ['slug' => 'sla_high_attention', 'value' => '10', 'default' => '10', 'lang' => 'ticketit::admin.sla-high-attention'],
            ['slug' => 'sla_high_warning',   'value' => '20', 'default' => '20', 'lang' => 'ticketit::admin.sla-high-warning'],
            ['slug' => 'sla_high_critical',  'value' => '30', 'default' => '30', 'lang' => 'ticketit::admin.sla-high-critical'],
        ];

        $payload = array_map(
            fn (array $setting): array => array_merge($setting, ['created_at' => $now, 'updated_at' => $now]),
            $settings
        );

        DB::table('ticketit_settings')->upsert(
            $payload,
            ['slug'],
            ['value', 'default', 'lang', 'updated_at']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'sla_low_attention', 'sla_low_warning', 'sla_low_critical',
            'sla_med_attention', 'sla_med_warning', 'sla_med_critical',
            'sla_high_attention', 'sla_high_warning', 'sla_high_critical',
        ];

        DB::table('ticketit_settings')->whereIn('slug', $slugs)->delete();
    }
};
