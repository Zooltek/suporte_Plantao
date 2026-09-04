<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit_audits', function (Blueprint $table) {
            $table->string('event')->after('operation')->default('updated'); // created, updated, deleted, status_changed, etc.
            $table->string('field')->nullable()->after('event');
            $table->text('old_value')->nullable()->after('field');
            $table->text('new_value')->nullable()->after('old_value');
        });
    }

    public function down(): void
    {
        Schema::table('ticketit_audits', function (Blueprint $table) {
            $table->dropColumn(['event', 'field', 'old_value', 'new_value']);
        });
    }
};
