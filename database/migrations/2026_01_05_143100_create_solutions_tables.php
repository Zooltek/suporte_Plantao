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
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->longText('searchable_content');
            $table->unsignedInteger('sort_order');
            $table->string('background');
            $table->unsignedInteger('likes');
            $table->unsignedInteger('dislikes');
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedInteger('views');
            $table->unsignedInteger('status');
            $table->longText('uploads');
            $table->string('tags');
            $table->timestamps();
        });

        Schema::create('solutions_category', function (Blueprint $table) {
            $table->id('category_id');
            $table->unsignedInteger('parent_id')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedTinyInteger('visible')->default(1);
            $table->unsignedInteger('ticket_category_id')->default(1);
            $table->unsignedInteger('profile')->default(0);
            $table->unsignedTinyInteger('header')->default(1);
            $table->unsignedTinyInteger('priority_id')->default(1);
            $table->timestamps();
        });

        Schema::create('solutions_category_description', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keyword')->nullable();
            $table->longText('html_header')->nullable();
        });

        Schema::create('solutions_category_form', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->primary();
            $table->longText('html')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solutions');
        Schema::dropIfExists('solutions_category');
        Schema::dropIfExists('solutions_category_description');
        Schema::dropIfExists('solutions_category_form');
    }
};
