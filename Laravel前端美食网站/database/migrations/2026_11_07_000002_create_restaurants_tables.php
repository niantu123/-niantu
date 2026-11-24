<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('city', 30)->default('上海');
            $table->string('district', 30);
            $table->string('address', 120);
            $table->string('landmark', 80)->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('hours', 60);
            $table->unsignedSmallInteger('avg_price')->default(80);
            $table->decimal('rating', 2, 1)->default(4.5);
            $table->string('cuisine', 30);
            $table->string('cover');
            $table->text('intro');
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('role', 20)->default('chef');
            $table->string('title', 60);
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('years')->default(5);
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('category', 20)->default('主菜');
            $table->decimal('price', 8, 2);
            $table->string('intro', 140)->nullable();
            $table->boolean('is_signature')->default(false);
            $table->unsignedTinyInteger('sort')->default(0);
        });

        Schema::create('recipe_restaurant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2)->nullable();
            $table->unique(['recipe_id', 'restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_restaurant');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('restaurant_staff');
        Schema::dropIfExists('restaurants');
    }
};
