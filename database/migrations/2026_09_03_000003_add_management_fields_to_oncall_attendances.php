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
        Schema::table('oncall_attendances', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true)->after('is_resolved');
            $table->integer('adjusted_duration_minutes')->nullable()->after('duration_minutes');
            $table->text('admin_notes')->nullable()->after('solution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oncall_attendances', function (Blueprint $table) {
            $table->dropColumn(['is_approved', 'adjusted_duration_minutes', 'admin_notes']);
        });
    }
};
