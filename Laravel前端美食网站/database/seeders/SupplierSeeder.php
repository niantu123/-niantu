<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /** 图片沿用 public/images/restaurants/ 下已本地化的店内实拍图 */
    private const IMG = '/images/restaurants/';

    public function run(): void
    {
        Supplier::query()->delete();

        $suppliers = [
            [
                'name' => '老周蔬菜铺',
                'city' => '上海',
                'district' => '徐汇区',
                'address' => '永康路菜市场 12 号摊位',
                'landmark' => '东门进去第三排，绿色遮阳棚那家',
                'lat' => 31.207800,
                'lng' => 121.454200,
                'phone' => '021-6437 1208',
                'hours' => '05:30–19:00',
                'category' => '蔬菜',
                'delivery' => '市区满 50 元免运费，凌晨 4 点前下单当天送',
                'rating' => 4.8,
                'cover' => self::IMG.'green-booths.jpg',
                'intro' => '在永康路菜市场摆了十九年摊，菜是每天凌晨从南汇和崇明的地头直接拉回来的。叶菜不泡水，称完再送一把小葱。附近不少小馆子的青菜都从这儿拿。',
                'features' => ['当日到货', '可开票', '支持批发', '免费配葱'],
                'products' => [
                    ['本地小菠菜', '蔬菜', 8.5, '500g', '叶片薄，焯水十秒就够', true],
                    ['沙地土豆', '蔬菜', 3.2, '500g', '黄心，适合炖和焖', false],
                    ['水东芥菜', '蔬菜', 6.8, '500g', '微微发苦，炒腊肉最搭', false],
                    ['鲜香菇', '蔬菜', 12.0, '500g', '菌盖厚实，当天采当天卖', true],
                    ['荷兰豆', '蔬菜', 15.0, '500g', '掐得断的才嫩', false],
                    ['春笋', '蔬菜', 22.0, '500g', '清明前后的头茬，涩味轻', false],
                ],
                'images' => [
                    ['green-booths.jpg', '清晨的菜摊'],
                    ['dining-atmosphere.jpg', '码得整齐的叶菜'],
                    ['interior-corner.jpg', '摊位的操作台'],
                ],
            ],
            [
                'name' => '阿芬鲜肉铺',
                'city' => '上海',
                'district' => '黄浦区',
                'address' => '宁波路 88 号 1 楼',
                'landmark' => '近南京东路，门口挂着铜钩的那家',
                'lat' => 31.238100,
                'lng' => 121.479300,
                'phone' => '021-6321 4477',
                'hours' => '06:00–18:30',
                'category' => '肉禽',
                'delivery' => '满 100 元起送，可代切、代剁',
                'rating' => 4.7,
                'cover' => self::IMG.'industrial-hall.jpg',
                'intro' => '老板娘阿芬从十六铺的肉案学徒做起，现在每天凌晨三点去江杨北路挑货。黑猪五花只卖当天的，卖完就收摊。要什么部位、切多厚，说一声就给你处理好。',
                'features' => ['当日现杀', '可代切', '冷鲜配送', '老客留货'],
                'products' => [
                    ['黑猪五花', '肉禽', 42.0, '500g', '五层分明，做红烧肉最稳', true],
                    ['猪肋排', '肉禽', 38.0, '500g', '骨头细，肉厚', true],
                    ['猪前腿肉', '肉禽', 26.0, '500g', '肥瘦三七，适合绞馅', false],
                    ['牛腩', '肉禽', 56.0, '500g', '带筋，炖两小时软糯', false],
                    ['崇明散养土鸡', '肉禽', 88.0, '只（约 1.5kg）', '养足 180 天，汤色清亮', true],
                    ['鸭腿', '肉禽', 18.0, '两只', '皮厚油足，卤着吃', false],
                ],
                'images' => [
                    ['industrial-hall.jpg', '案台与冷柜'],
                    ['bar-corner.jpg', '分切区'],
                    ['fine-dining.jpg', '当日到货'],
                ],
            ],
            [
                'name' => '江记水产',
                'city' => '上海',
                'district' => '杨浦区',
                'address' => '平凉路 2100 号水产市场 B 区 6 号',
                'landmark' => '市场北门直走到底，蓝色水槽那家',
                'lat' => 31.274600,
                'lng' => 121.542900,
                'phone' => '021-6543 2210',
                'hours' => '05:00–17:00',
                'category' => '水产',
                'delivery' => '活鲜当天送，泡沫箱加冰，满 150 元起送',
                'rating' => 4.6,
                'cover' => self::IMG.'window-seats.jpg',
                'intro' => '凌晨从舟山和启东的船上下来的货，五点到市场。虾要活的，带鱼要银亮的，卖的就是这个新鲜。可以帮杀、帮刮鳞、帮去虾线。',
                'features' => ['活鲜直供', '可代处理', '加冰打包', '凌晨到货'],
                'products' => [
                    ['活基围虾', '水产', 68.0, '500g', '个头均匀，白灼最甜', true],
                    ['舟山带鱼', '水产', 32.0, '500g', '银白有光泽，肉厚', true],
                    ['海鲈鱼', '水产', 28.0, '条（约 600g）', '可代杀，清蒸正好', false],
                    ['蛏子', '水产', 22.0, '500g', '已吐净沙，葱油一爆', false],
                    ['小黄鱼', '水产', 26.0, '500g', '二两左右一条，干煎', false],
                    ['蛤蜊', '水产', 14.0, '500g', '做汤鲜，吐沙干净', false],
                ],
                'images' => [
                    ['window-seats.jpg', '活鲜水槽'],
                    ['reading-corner.jpg', '清晨到货'],
                    ['terrace-seats.jpg', '分拣打包'],
                ],
            ],
            [
                'name' => '禾丰粮油行',
                'city' => '上海',
                'district' => '普陀区',
                'address' => '曹杨路 1020 号 3 号铺',
                'landmark' => '地铁 3 号口出来左转 50 米',
                'lat' => 31.252300,
                'lng' => 121.413100,
                'phone' => '021-6257 8890',
                'hours' => '07:30–20:00',
                'category' => '粮油',
                'delivery' => '整箱整袋可送，满 80 元免运费',
                'rating' => 4.5,
                'cover' => self::IMG.'interior-corner.jpg',
                'intro' => '开了二十多年的老粮油店，米面油都是整批从产地拉。东北五常米每年只进两季，卖完就等下一年。酱油醋挑的是几家本地老厂的货，味道正。',
                'features' => ['产地直采', '可开票', '整箱优惠', '老厂调味'],
                'products' => [
                    ['五常稻花香米', '粮油', 58.0, '5kg', '当年新米，冷水下锅也香', true],
                    ['菜籽油', '粮油', 46.0, '5L', '小榨工艺，颜色深一点', true],
                    ['特级生抽', '粮油', 18.0, '500ml', '本地老厂，氨基酸态氮 1.2', false],
                    ['镇江陈醋', '粮油', 12.0, '500ml', '三年陈，酸得有层次', false],
                    ['手擀面', '粮油', 9.0, '500g', '当天现擀，煮三分钟', false],
                    ['红薯粉条', '粮油', 16.0, '500g', '久煮不烂，炖肉吸汁', false],
                ],
                'images' => [
                    ['interior-corner.jpg', '货架'],
                    ['bright-dining.jpg', '米面区'],
                    ['hall-booths.jpg', '整袋装货'],
                ],
            ],
            [
                'name' => '小满烘焙原料',
                'city' => '上海',
                'district' => '长宁区',
                'address' => '武夷路 320 号 1 楼',
                'landmark' => '弄堂口白色招牌，隔壁是花店',
                'lat' => 31.216900,
                'lng' => 121.426500,
                'phone' => '021-6278 3311',
                'hours' => '09:00–20:00',
                'category' => '烘焙',
                'delivery' => '黄油奶油全程冷链，满 120 元起送',
                'rating' => 4.7,
                'cover' => self::IMG.'bright-dining.jpg',
                'intro' => '专门给家庭烘焙备料的小店。面粉按用途分了好几种，淡奶油和黄油全程冷链，酵母和巧克力都是小包装，做一次用一份，不会放到过期。',
                'features' => ['冷链配送', '小包装', '可拆零', '配方咨询'],
                'products' => [
                    ['低筋面粉', '烘焙', 12.0, '1kg', '做戚风和饼干都合适', true],
                    ['动物性淡奶油', '烘焙', 32.0, '1L', '乳脂 35%，打发稳定', true],
                    ['发酵黄油', '烘焙', 28.0, '500g', '奶香重，可颂必备', false],
                    ['耐高糖酵母', '烘焙', 6.0, '100g', '小包装，开封后不易失效', false],
                    ['黑巧克力 70%', '烘焙', 36.0, '500g', '烘焙用币状，融化快', false],
                    ['细砂糖', '烘焙', 8.0, '1kg', '颗粒细，易打发', false],
                ],
                'images' => [
                    ['bright-dining.jpg', '原料货架'],
                    ['dining-hall.jpg', '操作台'],
                    ['bar-corner.jpg', '冷藏柜'],
                ],
            ],
        ];

        foreach ($suppliers as $data) {
            $products = $data['products'] ?? [];
            $images = $data['images'] ?? [];

            unset($data['products'], $data['images']);

            $supplier = Supplier::create($data);

            foreach ($products as $i => [$name, $category, $price, $unit, $intro, $signature]) {
                $supplier->products()->create([
                    'name' => $name,
                    'category' => $category,
                    'price' => $price,
                    'unit' => $unit,
                    'intro' => $intro,
                    'is_signature' => $signature,
                    'sort' => $i,
                ]);
            }

            foreach ($images as $i => [$file, $caption]) {
                $supplier->images()->create([
                    'path' => self::IMG.$file,
                    'caption' => $caption,
                    'sort' => $i,
                ]);
            }
        }

        // 食材店评价：和餐厅评价同表，用 supplier_id 区分
        $comments = [
            '老周蔬菜铺' => [
                ['清炒党', 5, '菠菜是真嫩，回家洗两遍就能下锅，没有想象中那么多沙。', 5],
                ['小馆子老板', 5, '店里青菜一直在这儿拿，早上六点就送到后门，从没缺过货。', 28],
            ],
            '阿芬鲜肉铺' => [
                ['红烧肉研究所', 5, '五花五层很均匀，让切两指宽就真给切两指宽，不用再说一遍。', 7],
                ['厨房新手小李', 4, '肉是好的，就是周末去晚了肋排经常卖完，建议提前打电话留货。', 36],
            ],
            '江记水产' => [
                ['海边长大', 5, '带鱼眼睛清亮、鱼身有银光，这个新鲜度在这个价位算很好了。', 11],
                ['下班做饭', 4, '帮杀帮处理很省事，就是市场停车不太方便。', 44],
            ],
            '禾丰粮油行' => [
                ['东北人', 5, '五常米一开袋就有米香，煮出来颗粒分明，不是那种一煮就烂的。', 19],
            ],
            '小满烘焙原料' => [
                ['甜牙齿', 5, '淡奶油冷链送过来还是冰的，小包装酵母对家庭烘焙太友好了。', 3],
                ['周末烤面包', 4, '面粉种类很全，老板还会告诉你哪个适合做吐司，省得自己查。', 50],
            ],
        ];

        foreach ($comments as $name => $items) {
            $supplier = Supplier::where('name', $name)->first();

            if (! $supplier) {
                continue;
            }

            foreach ($items as [$nickname, $rating, $content, $hoursAgo]) {
                $supplier->comments()->create([
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
