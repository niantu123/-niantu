<?php

namespace Database\Seeders;

use App\Models\Recipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserWorkSeeder extends Seeder
{
    /**
     * 一道厨友上传的作品，用于演示「厨友作品」分类
     */
    public function run(): void
    {
        $user = DB::table('users')->where('email', 'demo@shiguang.test')->first();

        if (! $user) {
            return;
        }

        if (Recipe::where('user_id', $user->id)->exists()) {
            return;
        }

        $recipe = Recipe::create([
            'user_id' => $user->id,
            'title' => '番茄炒蛋',
            'category' => '家常菜',
            'cover' => '/images/recipes/fan-qie-chao-dan.png',
            'intro' => '番茄先出汁、鸡蛋后回锅，这道菜的关键在于两样东西分开炒，最后才拌在一起。',
            'prep_minutes' => 5,
            'cook_minutes' => 10,
            'difficulty' => '简单',
            'servings' => 2,
            'calories' => 240,
            'author' => $user->name,
        ]);

        $recipe->ingredients()->createMany([
            ['name' => '番茄', 'amount' => '2 个', 'sort' => 0],
            ['name' => '鸡蛋', 'amount' => '3 个', 'sort' => 1],
            ['name' => '小葱', 'amount' => '1 根', 'sort' => 2],
            ['name' => '白糖', 'amount' => '1 小勺', 'sort' => 3],
            ['name' => '盐', 'amount' => '适量', 'sort' => 4],
        ]);

        $recipe->steps()->createMany([
            ['step_no' => 1, 'content' => '番茄顶部划十字，开水烫三十秒后去皮，切成滚刀块。'],
            ['step_no' => 2, 'content' => '鸡蛋打散，加一小撮盐和半勺清水，搅到看不见明显蛋白。'],
            ['step_no' => 3, 'content' => '热锅多油，倒入蛋液，边缘凝固就用铲子推成大块，七成熟盛出。'],
            ['step_no' => 4, 'content' => '锅里留底油，下番茄块中火炒到出红汁，加一小勺糖提鲜。'],
            ['step_no' => 5, 'content' => '倒回鸡蛋，翻炒十秒让蛋块裹上汤汁，撒葱花出锅。'],
        ]);
    }
}
