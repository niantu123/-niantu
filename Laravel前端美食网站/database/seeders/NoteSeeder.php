<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Note;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NoteSeeder extends Seeder
{
    /**
     * 演示做菜食记：几篇真实感的笔记，附带评论
     */
    public function run(): void
    {
        if (Note::exists()) {
            return;
        }

        $users = DB::table('users')->whereIn('email', [
            'demo@shiguang.test',
            'boss@shiguang.test',
            '1358883818@qq.com',
        ])->pluck('id', 'email');

        $demo = $users['demo@shiguang.test'] ?? null;
        $boss = $users['boss@shiguang.test'] ?? null;
        $admin = $users['1358883818@qq.com'] ?? null;

        $notes = [
            [
                'user_id' => $demo,
                'title' => '第三次做红烧肉，终于不柴了',
                'cover' => '/images/recipes/hong-shao-rou.png',
                'content' => "前两次失败都败在火候上：第一次大火猛烧，肉紧得像橡皮；第二次图省事没用冰糖，颜色发黑还发苦。\n\n这回学乖了，冰糖小火慢慢炒到枣红色再下肉，每块都裹上糖色；加水一次加足，热水，没过肉面，然后转小火焖四十分钟，中途一次都没开盖。\n\n收汁的时候守在锅边，汤汁冒大泡、能挂在勺背上就立刻关火。肉皮是糯的，肥肉部分入口即化，瘦肉也能咬出汁水。最大的心得：红烧肉急不来，四十分钟一分钟都不能少。",
            ],
            [
                'user_id' => $boss,
                'title' => '砂锅买对了，煲仔饭成功率翻倍',
                'cover' => '/images/recipes/fan-qie-niu-nan.png',
                'content' => "店里后厨换了一批新砂锅，借这个机会说说挑选心得。\n\n一定要选窑变釉、锅底平整的粗陶砂锅，新锅先煮一次粥「开锅」，米浆会把细缝填住，之后不容易裂。煲仔饭的锅巴全靠锅底的蓄热，薄底锅受热太快容易糊，厚底的才稳。\n\n另外腊肉腊肠要最后五分钟再铺上去，靠蒸汽逼出油香，比一开始就放进去香得多。家里人少的话，买三升的容量就够，太大反而占灶眼。",
            ],
            [
                'user_id' => $admin,
                'title' => '炖汤之前，菌菇到底要不要洗',
                'cover' => '/images/recipes/jun-gu-ji-tang.png',
                'content' => "问了三位阿姨三个答案，自己试了一周，结论如下。\n\n干香菇、竹荪这类一定冲水再泡，泡完的水沉淀后取上层清的倒回锅里，鲜味不流失；新鲜口蘑表面只要不脏，用湿布擦一遍就行，泡水反而吸味。\n\n炖汤全程小火，汤面保持「虾眼泡」的状态最理想——大滚会把汤炖浑，还发苦。盐最后放，早放肉会发柴。按这个来，一锅汤清而不寡。",
            ],
            [
                'user_id' => $demo,
                'title' => '葱油拌面的葱，要熬两遍',
                'cover' => null,
                'content' => "以前图快，葱段下锅一次炸完，结果是葱苦油浑。\n\n后来照着弄堂里的做法分两遍：第一遍小火把葱白熬到微黄先捞出来，第二遍再下葱绿熬到焦香。两遍混合的葱油，香味有层次，也不会发苦。\n\n拌面的时候再加半勺猪油、一勺生抽半勺老抽，面条煮到九分熟直接进锅拌，趁着余温把酱汁吸进去。成本五块钱，比外面三十八的还好吃。",
            ],
        ];

        $created = [];

        foreach ($notes as $i => $data) {
            if (! $data['user_id']) {
                continue;
            }

            $created[] = Note::create($data + [
                'created_at' => now()->subDays(count($notes) - $i),
                'updated_at' => now()->subDays(count($notes) - $i),
            ]);
        }

        if (empty($created)) {
            return;
        }

        $comments = [
            [$created[0]?->id, $admin, '这步「热水一次加足」是关键，冷水确实会让肉缩。'],
            [$created[0]?->id, $boss, '糖色炒到枣红而不是黑红，很多新手都败在这一步，写得准。'],
            [$created[1]?->id, $demo, '开锅煮粥这个方法跟我妈说的一样，砂锅确实不能偷懒。'],
            [$created[2]?->id, $demo, '「虾眼泡」这个说法太形象了，下次炖汤照这个标准来。'],
            [$created[3]?->id ?? null, $boss, '葱油分两遍熬是我们店的老规矩，葱绿最后放才不苦。'],
        ];

        foreach ($comments as $i => [$noteId, $userId, $content]) {
            if (! $noteId || ! $userId) {
                continue;
            }

            Comment::create([
                'note_id' => $noteId,
                'user_id' => $userId,
                'nickname' => DB::table('users')->where('id', $userId)->value('name'),
                'rating' => 5, // 食记评论不打分，字段非空给默认值
                'content' => $content,
                'created_at' => now()->subHours(24 - $i * 3),
                'updated_at' => now()->subHours(24 - $i * 3),
            ]);
        }
    }
}
