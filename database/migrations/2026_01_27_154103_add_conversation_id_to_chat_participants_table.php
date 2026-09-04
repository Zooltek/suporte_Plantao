<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('chat_participants', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id');

            $table->foreign('conversation_id')
                ->references('id')
                ->on('chats')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('chat_participants', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
