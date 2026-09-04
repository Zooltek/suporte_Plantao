<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_messages', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('conversation_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('whatsapp_messages', 'is_internal')) {
                $table->boolean('is_internal')
                    ->default(false)
                    ->after('provider_message_id')
                    ->index();
            }

            if (! Schema::hasColumn('whatsapp_messages', 'deleted_by')) {
                $table->foreignId('deleted_by')
                    ->nullable()
                    ->after('is_internal')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('whatsapp_messages', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('deleted_by')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'deleted_by')) {
                $table->dropConstrainedForeignId('deleted_by');
            }

            if (Schema::hasColumn('whatsapp_messages', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            foreach (['deleted_at', 'is_internal'] as $column) {
                if (Schema::hasColumn('whatsapp_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
