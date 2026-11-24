<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Recipe;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        Comment::query()->delete();

        $data = [
            '本帮红烧肉' => [
                ['阿May', 5, '第一次炒糖色成功！按你说的枣红色下肉，一点没苦味。', 2],
                ['厨房新手小李', 4, '肉有点柴，可能我煎太久了，下次缩短两分钟试试。', 5],
                ['老周', 5, '静置十分钟这步以前总忽略，确实更入味。', 26],
            ],
            '葱油拌面' => [
                ['一人食日记', 5, '葱白葱绿分开炸是关键，以前一锅炸完总是发苦。', 1],
                ['夜宵选手', 5, '加了一勺猪油，香得不行，五分钟吃完。', 8],
            ],
            '麻婆豆腐' => [
                ['川妹子', 5, '分三次勾芡这招太有用了，汤汁挂得很好。', 3],
                ['不吃辣的猫', 4, '减了豆瓣酱的量，微辣也很下饭。', 12],
            ],
            '清蒸鲈鱼' => [
                ['江南人', 5, '倒掉腥水再淋豉油，这一步决定了整道菜的成败。', 4],
            ],
            '免烤提拉米苏' => [
                ['甜牙齿', 5, '手指饼蘸一秒刚好，蘸久了会烂。', 6],
                ['烘焙小白', 4, '冷藏四小时有点急，我放了六小时更好切。', 20],
            ],
        ];

        foreach ($data as $title => $comments) {
            $recipe = Recipe::where('title', $title)->first();

            if (! $recipe) {
                continue;
            }

            foreach ($comments as [$nickname, $rating, $content, $hoursAgo]) {
                Comment::create([
                    'recipe_id' => $recipe->id,
                    'nickname' => $nickname,
                    'rating' => $rating,
                    'content' => $content,
                    'created_at' => now()->subHours($hoursAgo),
                    'updated_at' => now()->subHours($hoursAgo),
                ]);
            }
        }

        // 餐厅评价：写入同一张 comments 表，用 restaurant_id 区分
        $restaurantComments = [
            '弄堂里·本帮菜馆' => [
                ['小笼包爱好者', 5, '红烧肉确实做到了入口即化，酱汁拌饭能多吃一碗。周三晚上去不用等位。', 6],
                ['吃货老张', 4, '菜是好的，就是店面小，六个人以上基本坐不下，建议提前订。', 30],
                ['Amy', 5, '服务员会主动提醒菜量，两个人点三个菜刚好，没有硬推。', 52],
            ],
            '川味小馆' => [
                ['成都来的', 5, '麻婆豆腐的麻是花椒现磨的，不是花椒粉糊弄，这点很加分。', 12],
                ['不吃香菜', 3, '味道够辣，但那天上菜有点慢，等了快四十分钟。', 40],
            ],
            '面屋十番' => [
                ['加班到九点', 4, '十点半还开着，一碗豚骨拉面下肚才算收工。汤偏咸，可以让厨房少盐。', 9],
            ],
        ];

        foreach ($restaurantComments as $name => $comments) {
            $restaurant = Restaurant::where('name', $name)->first();

            if (! $restaurant) {
                continue;
            }

            foreach ($comments as [$nickname, $rating, $content, $hoursAgo]) {
                Comment::create([
                    'restaurant_id' => $restaurant->id,
                    'nickname' => $nickname,
                    'rating' => $rating,
                    'content' => $content,
                    'created_at' => now()->subHours($hoursAgo),
                    'updated_at' => now()->subHours($hoursAgo),
                ]);
            }
        }
    }
}
