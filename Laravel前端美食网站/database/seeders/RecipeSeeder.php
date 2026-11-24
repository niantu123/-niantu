<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        Recipe::query()->delete();

        $recipes = [
            [
                'title' => '本帮红烧肉',
                'category' => '家常菜',
                'cover' => '/images/recipes/hong-shao-rou.png',
                'intro' => '浓油赤酱的代表作。糖色炒到位、全程加热水，肉皮才会颤而不散，肥肉入口即化。',
                'prep_minutes' => 15,
                'cook_minutes' => 60,
                'difficulty' => '简单',
                'servings' => 4,
                'calories' => 680,
                'author' => '灶台边的老陈',
                'ingredients' => [
                    ['五花肉', '600 克'], ['冰糖', '30 克'], ['生抽', '3 汤匙'], ['老抽', '1 汤匙'],
                    ['料酒', '3 汤匙'], ['姜', '5 片'], ['葱', '2 段'], ['八角', '1 颗'], ['热水', '800 毫升'],
                ],
                'steps' => [
                    '五花肉切成 3 厘米见方的块，冷水下锅，加姜片和 1 汤匙料酒，水开后煮 3 分钟撇净浮沫，捞出用温水冲洗。',
                    '锅烧热不放油，肉块皮朝下码入，中小火煎到四面微黄、逼出多余油脂后盛出。',
                    '锅内留薄薄一层油，放冰糖小火慢炒，糖化开后转成枣红色、开始冒细密小泡时立刻下肉。',
                    '倒入肉块快速翻炒裹上糖色，加生抽、老抽和剩下的料酒炒匀。',
                    '一次性加入热水没过肉面，放姜片、葱段、八角，大火烧开后转小火加盖焖 50 分钟。',
                    '开盖转大火收汁，汤汁能挂在勺背上就关火，静置 10 分钟再上桌更入味。',
                ],
                'restaurants' => [['弄堂里·本帮菜馆', 88], ['江南灶', 78]],
            ],
            [
                'title' => '葱油拌面',
                'category' => '面食',
                'cover' => '/images/recipes/cong-you-ban-mian.png',
                'intro' => '一人食的天花板。葱要分两次下锅，葱白出香、葱绿出色，油温不能高，否则整锅发苦。',
                'prep_minutes' => 5,
                'cook_minutes' => 10,
                'difficulty' => '简单',
                'servings' => 1,
                'calories' => 520,
                'author' => '一只小锅',
                'ingredients' => [
                    ['细面', '150 克'], ['小葱', '6 根'], ['生抽', '2 汤匙'], ['老抽', '1 茶匙'],
                    ['白糖', '1 茶匙'], ['食用油', '60 毫升'], ['猪油', '1 茶匙（可选）'],
                ],
                'steps' => [
                    '小葱洗净后彻底擦干，葱白和葱绿分开切成长段。',
                    '冷油下葱白，小火慢炸 5 分钟至微黄，再放葱绿炸到焦褐、香气浓郁，捞出葱段留油。',
                    '葱油里加生抽、老抽、白糖，小火煮到边缘冒小泡即关火，葱油汁完成。',
                    '面条煮到刚断生就捞出沥干，趁热拌一勺猪油让面更滑。',
                    '淋 2 到 3 勺葱油汁，撒上炸好的葱段，快速拌匀立刻吃。',
                ],
                'restaurants' => [['面屋十番', 28]],
            ],
            [
                'title' => '番茄牛腩煲',
                'category' => '炖菜',
                'cover' => '/images/recipes/fan-qie-niu-nan.png',
                'intro' => '番茄要分两次放：一半炒成酱打底，一半最后放保留果肉，汤才又浓又有层次。',
                'prep_minutes' => 15,
                'cook_minutes' => 90,
                'difficulty' => '中等',
                'servings' => 4,
                'calories' => 560,
                'author' => '南灶北炉',
                'ingredients' => [
                    ['牛腩', '800 克'], ['番茄', '4 个'], ['洋葱', '半个'], ['番茄膏', '2 汤匙'],
                    ['姜', '4 片'], ['八角', '2 颗'], ['桂皮', '1 小段'], ['冰糖', '10 克'], ['盐', '适量'],
                ],
                'steps' => [
                    '牛腩切大块，冷水下锅焯水，水开后撇去浮沫，捞出用温水洗净。',
                    '番茄顶部划十字，开水烫 30 秒后去皮，两个切碎、两个切块备用。',
                    '锅中少油炒香洋葱丁和姜片，下切碎的番茄和番茄膏，中火炒出红油。',
                    '放入牛腩翻炒均匀，加没过食材的热水、八角和桂皮，大火烧开转小火炖 60 分钟。',
                    '加入番茄块和冰糖，继续炖 20 分钟至牛腩软烂。',
                    '最后加盐调味，大火略收汤汁，撒葱花出锅。',
                ],
                'restaurants' => [['弄堂里·本帮菜馆', 68]],
            ],
            [
                'title' => '清蒸鲈鱼',
                'category' => '海鲜',
                'cover' => '/images/recipes/qing-zheng-lu-yu.png',
                'intro' => '八分钟定成败。水开再上锅、蒸好立刻离火，鱼肉才呈蒜瓣状、清甜不腥。',
                'prep_minutes' => 10,
                'cook_minutes' => 8,
                'difficulty' => '简单',
                'servings' => 2,
                'calories' => 260,
                'author' => '江南小馆',
                'ingredients' => [
                    ['鲈鱼', '1 条（约 600 克）'], ['姜', '6 片'], ['小葱', '4 根'], ['蒸鱼豉油', '3 汤匙'],
                    ['料酒', '1 汤匙'], ['食用油', '2 汤匙'], ['盐', '少许'],
                ],
                'steps' => [
                    '鲈鱼处理干净，两面各划两刀，用料酒和少许盐抹匀，鱼腹和刀口塞入姜片，腌 10 分钟。',
                    '盘底垫两根葱段把鱼架空，让蒸汽能循环。',
                    '蒸锅水彻底烧开后放入鱼盘，大火蒸 8 分钟，关火焖 1 分钟。',
                    '取出后倒掉盘里的腥水，拣掉姜葱，重新铺上新鲜葱丝。',
                    '淋 3 汤匙蒸鱼豉油，另起锅把油烧到冒烟，直接浇在葱丝上激香。',
                ],
                'restaurants' => [['江南灶', 118]],
            ],
            [
                'title' => '干煸四季豆',
                'category' => '家常菜',
                'cover' => '/images/recipes/gan-bian-si-ji-dou.png',
                'intro' => '四季豆必须先煸到起皱断生，否则豆腥味重还有中毒风险，别贪快。',
                'prep_minutes' => 8,
                'cook_minutes' => 12,
                'difficulty' => '简单',
                'servings' => 2,
                'calories' => 240,
                'author' => '川味小馆',
                'ingredients' => [
                    ['四季豆', '400 克'], ['猪肉末', '80 克'], ['干辣椒', '6 个'], ['花椒', '1 茶匙'],
                    ['蒜', '4 瓣'], ['生抽', '1 汤匙'], ['盐', '适量'],
                ],
                'steps' => [
                    '四季豆掐去两头和老筋，掰成 5 厘米长的段，洗净后彻底擦干。',
                    '锅中放稍多的油，中火把四季豆煸 4 到 5 分钟，煸到表皮起皱发白后盛出。',
                    '倒出多余油，下肉末炒散至变色，加干辣椒、花椒、蒜末炒香。',
                    '倒回四季豆，加生抽和盐，大火翻炒 1 分钟让味道裹匀即可。',
                ],
                'restaurants' => [['川味小馆', 32]],
            ],
            [
                'title' => '菌菇老母鸡汤',
                'category' => '汤羹',
                'cover' => '/images/recipes/jun-gu-ji-tang.png',
                'intro' => '炖汤只做三件事：冷水下锅、小火不滚、最后放盐。汤清味厚，鸡肉不柴。',
                'prep_minutes' => 10,
                'cook_minutes' => 90,
                'difficulty' => '简单',
                'servings' => 4,
                'calories' => 380,
                'author' => '立冬的厨房',
                'ingredients' => [
                    ['老母鸡', '半只'], ['干香菇', '8 朵'], ['鲜口蘑', '150 克'], ['姜', '4 片'],
                    ['枸杞', '10 粒'], ['料酒', '1 汤匙'], ['盐', '适量'],
                ],
                'steps' => [
                    '干香菇提前用温水泡发 30 分钟，泡菇水静置后取上层清液留着。',
                    '鸡块冷水下锅焯水，加料酒和两片姜，水开后煮 3 分钟捞出洗净。',
                    '砂锅里放鸡块、姜片、香菇和泡菇水，再加足量冷水一次加够。',
                    '大火烧开后立刻转最小火，保持汤面微微冒泡，炖 80 分钟。',
                    '放入口蘑再炖 10 分钟，最后加枸杞和盐，关火焖 5 分钟。',
                ],
                'restaurants' => [['江南灶', 68]],
            ],
            [
                'title' => '麻婆豆腐',
                'category' => '川菜',
                'cover' => '/images/recipes/ma-po-dou-fu.png',
                'intro' => '麻、辣、烫、香、酥、嫩、鲜、活，八字诀里最容易丢的是「烫」和「嫩」。',
                'prep_minutes' => 8,
                'cook_minutes' => 12,
                'difficulty' => '中等',
                'servings' => 2,
                'calories' => 320,
                'author' => '川味小馆',
                'ingredients' => [
                    ['嫩豆腐', '1 盒（400 克）'], ['牛肉末', '100 克'], ['郫县豆瓣酱', '2 汤匙'],
                    ['豆豉', '1 茶匙'], ['花椒粉', '1 茶匙'], ['蒜苗', '2 根'], ['水淀粉', '3 汤匙'], ['高汤', '200 毫升'],
                ],
                'steps' => [
                    '豆腐切 2 厘米方块，放入加了盐的温水中浸 5 分钟，去豆腥并让豆腐更紧实。',
                    '热锅冷油下牛肉末，中火煸到酥香出油，盛出一半备用。',
                    '转小火下豆瓣酱和豆豉炒出红油，加高汤烧开。',
                    '轻轻推入豆腐，中小火烧 3 分钟，全程用锅铲背推动避免碎块。',
                    '分三次淋入水淀粉勾芡，每次都要等汤汁收稠再加下一次。',
                    '出锅撒花椒粉、蒜苗末和之前留的牛肉酥。',
                ],
                'restaurants' => [['川味小馆', 38]],
            ],
            [
                'title' => '免烤提拉米苏',
                'category' => '烘焙',
                'cover' => '/images/recipes/ti-la-mi-su.jpg',
                'intro' => '不用烤箱，成败全在蛋黄糊的温度和手指饼蘸咖啡的时间——一秒太久，三秒刚好。',
                'prep_minutes' => 25,
                'cook_minutes' => 5,
                'difficulty' => '简单',
                'servings' => 4,
                'calories' => 420,
                'author' => '甜点小满',
                'ingredients' => [
                    ['马斯卡彭', '250 克'], ['蛋黄', '2 个'], ['细砂糖', '50 克'], ['淡奶油', '150 毫升'],
                    ['手指饼干', '12 根'], ['浓缩咖啡', '120 毫升'], ['朗姆酒', '1 汤匙'], ['可可粉', '适量'],
                ],
                'steps' => [
                    '蛋黄加糖隔 60 度温水打发到颜色变浅、体积膨胀，离火放凉。',
                    '马斯卡彭回温后搅顺滑，分两次拌入蛋黄糊。',
                    '淡奶油打到六分发（有纹路但仍会流动），翻拌进芝士糊。',
                    '浓缩咖啡加朗姆酒放凉，手指饼两面各蘸 1 秒，铺满容器底部。',
                    '抹一层芝士糊，再铺一层蘸过咖啡的饼干，重复到容器八分满。',
                    '冷藏至少 4 小时，食用前筛可可粉。',
                ],
                'restaurants' => [['甜舍 Patisserie', 42]],
            ],
        ];

        foreach ($recipes as $data) {
            $ingredients = $data['ingredients'];
            $steps = $data['steps'];
            $links = $data['restaurants'];

            unset($data['ingredients'], $data['steps'], $data['restaurants']);

            $recipe = Recipe::create($data);

            foreach ($ingredients as $i => [$name, $amount]) {
                $recipe->ingredients()->create(['name' => $name, 'amount' => $amount, 'sort' => $i]);
            }

            foreach ($steps as $i => $content) {
                $recipe->steps()->create(['step_no' => $i + 1, 'content' => $content]);
            }

            foreach ($links as [$restaurantName, $price]) {
                $restaurant = Restaurant::where('name', $restaurantName)->first();

                if ($restaurant) {
                    $recipe->restaurants()->attach($restaurant->id, ['price' => $price]);
                }
            }
        }
    }
}
