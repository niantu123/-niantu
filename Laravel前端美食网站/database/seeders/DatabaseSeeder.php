<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * 初始化演示数据
     */
    public function run(): void
    {
        // 演示账号：demo@shiguang.test / 12345678
        DB::table('users')->updateOrInsert(
            ['email' => 'demo@shiguang.test'],
            [
                'name' => '演示厨友',
                'password' => Hash::make('12345678'),
                'role' => 'user',
                'bio' => '爱做饭，也爱吃别人做的饭。',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 管理员账号：1358883818@qq.com / 123456
        DB::table('users')->updateOrInsert(
            ['email' => '1358883818@qq.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'bio' => '网站管理员',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 餐厅管理员账号：boss@shiguang.test / shiguang123
        DB::table('users')->updateOrInsert(
            ['email' => 'boss@shiguang.test'],
            [
                'name' => '弄堂里店长',
                'password' => Hash::make('shiguang123'),
                'role' => 'restaurant_admin',
                'bio' => '弄堂里本帮菜馆的店长，管菜单也管灶台。',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 食材供应商账号：supplier@shiguang.test / shiguang123
        DB::table('users')->updateOrInsert(
            ['email' => 'supplier@shiguang.test'],
            [
                'name' => '老周',
                'password' => Hash::make('shiguang123'),
                'role' => 'supplier',
                'bio' => '在永康路菜市场卖菜十九年，凌晨四点就在摊上。',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 先餐厅后菜谱：菜谱要关联到餐厅
        $this->call([
            RestaurantSeeder::class,
            RecipeSeeder::class,
            CommentSeeder::class,
            UserWorkSeeder::class,
            NoteSeeder::class,
            SupplierSeeder::class,
        ]);

        // 把第一家店交给餐厅管理员账号管理
        $bossId = DB::table('users')->where('email', 'boss@shiguang.test')->value('id');
        $restaurantId = DB::table('restaurants')->where('name', '弄堂里·本帮菜馆')->value('id');

        if ($bossId && $restaurantId) {
            DB::table('restaurants')->where('id', $restaurantId)->update(['owner_id' => $bossId]);
        }

        // 把第一家食材店交给食材供应商账号管理
        $supplierId = DB::table('users')->where('email', 'supplier@shiguang.test')->value('id');
        $shopId = DB::table('suppliers')->where('name', '老周蔬菜铺')->value('id');

        if ($supplierId && $shopId) {
            DB::table('suppliers')->where('id', $shopId)->update(['owner_id' => $supplierId]);
        }
    }
}
