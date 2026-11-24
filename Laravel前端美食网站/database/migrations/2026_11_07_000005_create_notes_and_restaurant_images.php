<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 做菜食记：用户分享和记录做饭的过程与体会
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 60);
            $table->string('cover')->nullable();
            $table->text('content');
            $table->timestamps();
        });

        // 餐厅相册：店内环境图，展示在餐厅详情页的轮播里
        Schema::create('restaurant_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption', 40)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // 食记评论：同样挂在 comments 表上
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('note_id')->nullable()->after('restaurant_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('note_id');
        });

        Schema::dropIfExists('restaurant_images');
        Schema::dropIfExists('notes');
    }
};
