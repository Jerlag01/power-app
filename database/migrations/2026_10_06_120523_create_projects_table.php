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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expertise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('title');
            $table->string('location');
            $table->json('summary');
            $table->json('description');
            $table->json('highlights')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
