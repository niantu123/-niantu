<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 食材店：由「食材供应商」账号维护，像餐厅一样有封面、相册、主营商品和位置
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 60);
            $table->string('city', 30)->default('上海');
            $table->string('district', 30);
            $table->string('address', 120);
            $table->string('landmark', 80)->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('hours', 60);
            $table->string('category', 30);            // 主营类别：蔬菜 / 肉禽 / 水产 / 调味 / 粮油 / 烘焙
            $table->string('delivery', 60)->nullable(); // 配送与起批说明
            $table->decimal('rating', 2, 1)->default(4.5);
            $table->string('cover');
            $table->text('intro');
            $table->json('features')->nullable();
            $table->timestamps();
        });

        // 在售商品：对应餐厅的菜单，多了「规格」和「单位」
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('category', 20)->default('蔬菜');
            $table->decimal('price', 8, 2);
            $table->string('unit', 20)->default('500g');
            $table->string('intro', 140)->nullable();
            $table->boolean('is_signature')->default(false);
            $table->unsignedTinyInteger('sort')->default(0);
        });

        // 店铺相册：和餐厅一样用轮播展示
        Schema::create('supplier_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption', 40)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // 食材店留言：同样挂在 comments 表上
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('restaurant_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });

        Schema::dropIfExists('supplier_images');
        Schema::dropIfExists('supplier_products');
        Schema::dropIfExists('suppliers');
    }
};
