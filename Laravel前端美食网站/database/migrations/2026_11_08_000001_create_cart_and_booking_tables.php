<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 餐厅是否接在线预约（由店主在「餐厅管理」里开关）
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('accepts_booking')->default(false)->after('hours');
            $table->string('booking_notice', 120)->nullable()->after('accepts_booking');
        });

        // 购物车：一个用户对一件商品只有一行，重复加购累加数量
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'supplier_product_id']);
        });

        // 到店预约：日期 + 时段 + 人数，状态默认为待商家确认
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('book_date');
            $table->string('book_time', 10);
            $table->unsignedTinyInteger('party_size')->default(2);
            $table->string('contact_name', 30);
            $table->string('contact_phone', 20);
            $table->string('note', 200)->nullable();
            $table->string('status', 20)->default('pending'); // pending 待确认 / confirmed 已确认 / cancelled 已取消
            $table->timestamps();

            $table->index(['user_id', 'book_date']);
        });

        // 食材订单：结算时把购物车快照存下来，购物车随即清空
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_no', 24)->unique();
            $table->decimal('total', 10, 2)->default(0);
            $table->unsignedSmallInteger('item_count')->default(0);
            $table->string('contact_name', 30);
            $table->string('contact_phone', 20);
            $table->string('address', 120);
            $table->string('note', 200)->nullable();
            $table->string('status', 20)->default('pending'); // pending 待发货 / done 已完成 / cancelled 已取消
            $table->json('items');
            $table->timestamps();
        });

        // 已有餐厅默认都开放预约，只有几家小店除外
        $closed = ['面屋十番', '炭火烧烤铺', '甜舍 Patisserie'];

        DB::table('restaurants')->update([
            'accepts_booking' => true,
            'booking_notice' => '周末与节假日建议提前一天订位，座位最多保留 15 分钟。',
        ]);

        DB::table('restaurants')->whereIn('name', $closed)->update([
            'accepts_booking' => false,
            'booking_notice' => null,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('cart_items');

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['accepts_booking', 'booking_notice']);
        });
    }
};
