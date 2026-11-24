<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 40)->index();
            $table->string('cover');
            $table->text('intro');
            $table->unsignedSmallInteger('prep_minutes')->default(0);
            $table->unsignedSmallInteger('cook_minutes')->default(0);
            $table->string('difficulty', 10)->default('简单');
            $table->unsignedTinyInteger('servings')->default(2);
            $table->unsignedSmallInteger('calories')->nullable();
            $table->string('author', 40)->default('食光编辑');
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('amount', 40);
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_no');
            $table->text('content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');
    }
};
