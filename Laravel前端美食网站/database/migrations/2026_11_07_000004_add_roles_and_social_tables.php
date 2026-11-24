<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 用户：手机号（用户中心绑定手机用）
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
        });

        // 菜谱：上传者为空表示官方菜谱，非空表示厨友作品
        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'category']);
        });

        // 餐厅：绑定一位餐厅管理员
        Schema::table('restaurants', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // 评论：既可以是菜谱评论，也可以是餐厅评论
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->nullable()->after('recipe_id')->constrained()->cascadeOnDelete();
        });

        // recipe_id 放开为非必填，餐厅评价不带菜谱（change() 在 mysql 和 sqlite 上都能跑）
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('recipe_id')->nullable()->change();
        });

        // 收藏
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');

        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_id');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'category']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
