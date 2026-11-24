<?php

namespace App\Support;

use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Support\Collection;

/**
 * 美食助手：本地知识库问答
 *
 * 不依赖外部接口，直接读站内的菜谱 / 餐厅 / 食材店数据：
 * 1. 归一化问句，识别意图（做法 / 下馆子 / 买食材 / 闲聊 / 兜底）；
 * 2. 从问句里抽实体（菜名、菜系、区县、价位、食材），菜名支持口语别名与模糊匹配；
 * 3. 按意图查库，拼出带真实数字与跳转卡片的回答。
 */
class FoodAssistant
{
    /** 口语说法 → 菜谱全名 */
    private const DISH_ALIASES = [
        '西红柿炒鸡蛋' => '番茄炒蛋',
        '西红柿炒蛋' => '番茄炒蛋',
        '番茄炒鸡蛋' => '番茄炒蛋',
        '炒蛋' => '番茄炒蛋',
        '红烧肉' => '本帮红烧肉',
        '提拉米苏' => '免烤提拉米苏',
        '清蒸鱼' => '清蒸鲈鱼',
        '鲈鱼' => '清蒸鲈鱼',
        '四季豆' => '干煸四季豆',
        '老母鸡汤' => '菌菇老母鸡汤',
        '鸡汤' => '菌菇老母鸡汤',
        '牛腩煲' => '番茄牛腩煲',
        '葱油面' => '葱油拌面',
        '拌面' => '葱油拌面',
        '麻婆豆腐' => '麻婆豆腐',
        '豆腐' => '麻婆豆腐',
    ];

    /** 菜系口语 → 库里的菜系名 */
    private const CUISINE_ALIASES = [
        '本帮' => '本帮菜',
        '上海菜' => '本帮菜',
        '江浙' => '江浙菜',
        '杭帮' => '江浙菜',
        '川菜' => '川菜',
        '四川菜' => '川菜',
        '粤菜' => '粤菜',
        '广东菜' => '粤菜',
        '湘菜' => '湘菜',
        '湖南菜' => '湘菜',
        '日本料理' => '日料',
        '日式' => '日料',
        '日料' => '日料',
        '西餐' => '西餐',
        '西式' => '西餐',
        '火锅' => '火锅',
        '素食' => '素食',
        '吃素' => '素食',
        '烧烤' => '烧烤',
        '烤串' => '烧烤',
        '甜品' => '甜品',
        '甜点' => '甜品',
        '蛋糕' => '甜品',
        '面食' => '面食',
        '面条' => '面食',
        '面馆' => '面食',
    ];

    /** 区县 */
    private const DISTRICTS = ['静安', '黄浦', '徐汇', '长宁', '杨浦', '虹口'];

    /** 食材口语 → 菜谱里的食材名 */
    private const INGREDIENT_ALIASES = [
        '五花' => '五花肉',
        '西红柿' => '番茄',
        '洋柿子' => '番茄',
        '排骨' => '猪肋排',
        '基围虾' => '虾',
        '大虾' => '虾',
    ];

    /** 按食材猜店铺类别，用于店里没单卖时给替代建议 */
    private const CATEGORY_HINTS = [
        '肉禽' => ['肉', '排', '鸡', '鸭', '牛', '猪', '五花', '腩'],
        '水产' => ['鱼', '虾', '蟹', '贝', '蛤', '蛏', '螺'],
        '蔬菜' => ['菜', '豆', '瓜', '笋', '菇', '葱', '姜', '蒜', '椒', '茄', '薯', '萝卜'],
        '粮油' => ['油', '盐', '酱', '醋', '糖', '米', '面', '粉', '酒'],
        '烘焙' => ['奶油', '黄油', '酵母', '巧克力', '芝士', '奶酪'],
    ];

    /** 挑食材的经验，按店铺类别给 */
    private const PICKING_TIPS = [
        '蔬菜' => '看切口是不是还湿润、叶片挺不挺；同样大小挑更沉的，水分足。',
        '肉禽' => '颜色鲜红有光泽、按下去能回弹就是新鲜的；五花挑三层分明、肥瘦相间的。',
        '水产' => '鱼看鳃是不是鲜红、眼珠清不清亮；虾要壳亮紧实、头身连着，闻着只有淡淡海水味。',
        '粮油' => '米看生产日期和米粒完整度；油认准压榨工艺，开封后避光放、三个月内用完。',
        '烘焙' => '奶油和黄油认准「动物性」；面粉看蛋白质，低筋做蛋糕、高筋做面包。',
    ];

    /** 聊聊翻车时给的建议 */
    private const FIXES = [
        '太咸' => ['加点糖或一小块土豆同煮，能拉回咸味', '下次盐分两次放，收汁前再尝一次'],
        '糊了' => ['立刻关火，把没糊的部分盛出来，别在锅里搅', '糖色阶段最怕分心，炒到琥珀色就下肉'],
        '太油' => ['盛出后撇掉表层浮油，或加一把粉丝、萝卜吸油', '煸炒时先把多余的油逼出来盛走'],
        '不熟' => ['加盖小火延长时间，别靠大火硬烧', '切块均匀些，大小一致才熟得一致'],
    ];

    /** @var Collection<int, Recipe>|null */
    private ?Collection $recipeCache = null;

    /** @var Collection<int, string>|null */
    private ?Collection $ingredientCache = null;

    /**
     * 处理一句话，返回回答（内容 + 卡片 + 快捷追问 + 上下文）
     *
     * @param  array{recipe_id?: int, cuisine?: string, district?: string}  $context
     * @return array{content: string, cards: array, quick: array, intent: string, context: array}
     */
    public function reply(string $message, array $context = []): array
    {
        $raw = trim($message);
        $norm = $this->normalize($raw);

        if ($norm === '') {
            return $this->make(
                "想聊点什么？可以问我「红烧肉怎么做」「静安区有什么好吃的」「五花肉在哪买」。",
                [], $this->defaultQuick(), 'empty', $context
            );
        }

        if ($chat = $this->chitchat($norm, $context)) {
            return $chat;
        }

        $dish = $this->matchDish($norm);

        // 上一轮聊过某道菜时，允许「它／这道／那食材呢」这类省略说法接着问
        if (! $dish && ! empty($context['recipe_id']) && $this->refersToPrevious($norm)) {
            $dish = $this->recipes()->firstWhere('id', $context['recipe_id']);
        }

        $ingredient = $this->matchIngredient($norm);
        $cuisine = $this->matchCuisine($norm);
        $district = $this->matchDistrict($norm);
        $price = $this->parsePrice($norm);
        $sort = $this->parseSort($norm);
        $scene = $this->matchScene($norm);

        // 「刚才那家店人均多少」这类沿用上一轮的筛选条件
        if (! $district && ! empty($context['district']) && $this->refersToPrevious($norm)) {
            $district = $context['district'];
        }

        $restaurant = $this->matchRestaurant($norm);

        // 0. 点名某家店
        if ($restaurant) {
            return $this->answerRestaurantDetail($restaurant, $context);
        }

        // 1. 问买食材
        if ($this->wantsPurchase($norm) && ($ingredient || $dish)) {
            return $this->answerIngredient($ingredient, $dish, $context);
        }

        // 2. 只想逛逛食材店
        if ($this->wantsSupplierList($norm)) {
            return $this->answerSupplierList($context);
        }

        // 3. 站外的城市（站里只收录了本地这些店）——排在找餐厅之前，免得把外地问题答成本地店
        if ($this->wantsRestaurant($norm) || $this->wantsSuggestion($norm)) {
            $city = $this->matchForeignCity($norm);
            if ($city) {
                return $this->answerOffsiteCity($city, $context);
            }
        }

        // 4. 问去哪吃（排序、场景、本站城市、菜系、区县、预算都算）
        if ($this->wantsRestaurant($norm)
            && ($dish || $cuisine || $district || $sort
                || $this->wantsScene($norm) || $this->mentionsStationCity($norm))) {
            return $this->answerWhereToEat($dish, $cuisine, $district, $price, $context, $sort, $scene);
        }

        // 5. 单一菜名 → 直接给做法
        if ($dish) {
            return $this->answerRecipe($dish, $context);
        }

        // 6. 点名了一道站里没有的菜 → 交给大模型按常识讲，别硬塞推荐卡片
        if ($this->wantsRecipe($norm) && ($name = $this->guessDishName($norm))) {
            return $this->answerOffsiteDish($name, $context);
        }

        // 7. 只提到食材
        if ($ingredient) {
            return $this->answerIngredient($ingredient, null, $context);
        }

        // 8. 只提到菜系／区县／价位
        if ($cuisine || $district || $price['max'] || $price['min']) {
            return $this->answerWhereToEat(null, $cuisine, $district, $price, $context);
        }

        // 9. 想学做菜但没点名
        if ($this->wantsRecipe($norm)) {
            return $this->answerRecipeList($norm, $context);
        }

        // 10. 想吃点什么
        if ($this->wantsSuggestion($norm)) {
            return $this->answerRecipeList($norm, $context);
        }

        return $this->fallback($context);
    }

    /**
     * 站外的城市：站里收录的都是本地店，用户问别的城市就别装作有
     */
    private function answerOffsiteCity(string $city, array $context): array
    {
        $cities = $this->stationCities();
        $local = $cities ? implode('、', $cities) : '本地';

        return $this->make(
            "食光收录的餐厅只在{$local}，{$city}的店还没收录。\n"
            ."想先解馋的话，我可以在{$local}按口味帮你挑一家 —— 说说你想吃什么菜系或预算。",
            Restaurant::inRandomOrder()->take(2)->get()
                ->map(fn (Restaurant $r) => $this->restaurantCard($r))->all(),
            ['上海有什么好吃的', '人均 100 以内的店', '川味小馆招牌菜是什么'],
            'offsite',
            $context
        );
    }

    /**
     * 站里没有的菜：能答的交给大模型用常识讲，这边只负责别给错卡片
     */
    private function answerOffsiteDish(string $name, array $context): array
    {
        return $this->make(
            "站里还没收录「{$name}」这道菜的做法。\n"
            ."站内收录的是这些家常菜，想做哪道直接问我：",
            Recipe::inRandomOrder()->take(2)->get()
                ->map(fn (Recipe $r) => $this->recipeCard($r))->all(),
            ['推荐几道简单的菜', '哪道最适合新手', '红烧肉怎么做'],
            'offsite',
            $context
        );
    }

    /**
     * 站内已收录的城市（用来判断用户问的是不是外地）
     *
     * @return array<int, string>
     */
    private function stationCities(): array
    {
        return Restaurant::query()->distinct()->pluck('city')->filter()->values()->all();
    }

    /**
     * 场景诉求：约会、聚餐、夜宵、带娃这些
     */
    private function wantsScene(string $norm): bool
    {
        return $this->matchScene($norm) !== null;
    }

    /**
     * 取出句子里提到的场景词
     */
    private function matchScene(string $norm): ?string
    {
        foreach ([
            '约会', '聚餐', '请客', '一人食', '一个人吃', '带娃', '带小孩', '亲子',
            '夜宵', '宵夜', '下午茶', '生日', '纪念日', '商务', '包间', '安静', '氛围',
        ] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return $word;
            }
        }

        return null;
    }

    /**
     * 排序诉求：最贵、最便宜、评分最高……
     *
     * @return array{field: string, dir: string, label: string}|null
     */
    private function parseSort(string $norm): ?array
    {
        $rules = [
            [['最贵', '最豪华', '最高档', '最高端', '最烧钱', '价格最高', '人均最高'], 'avg_price', 'desc', '人均最高'],
            [['最便宜', '最实惠', '最划算', '最省钱', '价格最低', '人均最低'], 'avg_price', 'asc', '人均最低'],
            [['评分最高', '分数最高', '口碑最好', '评价最好', '最受欢迎', '人气最旺', '最有人气', '最热门', '最火'], 'rating', 'desc', '评分最高'],
            [['评分最低', '分数最低', '口碑最差'], 'rating', 'asc', '评分最低'],
        ];

        foreach ($rules as [$words, $field, $dir, $label]) {
            foreach ($words as $word) {
                if (mb_strpos($norm, $word) !== false) {
                    return ['field' => $field, 'dir' => $dir, 'label' => $label];
                }
            }
        }

        return null;
    }

    /**
     * 从一段回答里认出被点名的菜谱／餐厅／食材店／商品，按出现先后出卡片
     *
     * 大模型可能讲到本地候选之外的东西，用这个把卡片对齐到它真正推荐的那几家。
     *
     * @return array<int, array<string, mixed>>
     */
    public function mentionedCardsIn(string $text, int $limit = 3): array
    {
        $hits = [];

        foreach (Restaurant::query()->get(['id', 'name']) as $row) {
            $pos = mb_strpos($text, $row->name);
            if ($pos !== false) {
                $hits[] = ['pos' => $pos, 'type' => 'restaurant', 'id' => $row->id];
            }
        }

        foreach (Recipe::query()->get(['id', 'title']) as $row) {
            $pos = $this->hitPosition($text, $row->title);
            if ($pos !== false) {
                $hits[] = ['pos' => $pos, 'type' => 'recipe', 'id' => $row->id];
            }
        }

        foreach (Supplier::query()->get(['id', 'name']) as $row) {
            $pos = mb_strpos($text, $row->name);
            if ($pos !== false) {
                $hits[] = ['pos' => $pos, 'type' => 'supplier', 'id' => $row->id];
            }
        }

        foreach (SupplierProduct::query()->with('supplier')->get() as $row) {
            if (mb_strlen($row->name) < 2) {
                continue;
            }
            $pos = mb_strpos($text, $row->name);
            if ($pos !== false) {
                $hits[] = ['pos' => $pos, 'type' => 'product', 'id' => $row->id];
            }
        }

        if (! $hits) {
            return [];
        }

        usort($hits, fn (array $a, array $b) => $a['pos'] <=> $b['pos']);
        $hits = array_slice($hits, 0, $limit);

        $cards = [];
        foreach ($hits as $hit) {
            $card = match ($hit['type']) {
                'restaurant' => ($m = Restaurant::find($hit['id'])) ? $this->restaurantCard($m) : null,
                'recipe' => ($m = Recipe::find($hit['id'])) ? $this->recipeCard($m) : null,
                'supplier' => ($m = Supplier::find($hit['id'])) ? $this->supplierCard($m) : null,
                'product' => ($m = SupplierProduct::with('supplier')->find($hit['id'])) ? $this->productCard($m) : null,
                default => null,
            };

            if ($card) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * 菜名在回答里的位置
     *
     * 站内菜名常带前缀（「本帮红烧肉」），而模型聊天时会说简称（「红烧肉」），
     * 所以全名找不到时再试末尾 3～4 个字。
     */
    private function hitPosition(string $text, string $name): int|false
    {
        $pos = mb_strpos($text, $name);
        if ($pos !== false) {
            return $pos;
        }

        $length = mb_strlen($name);
        foreach ([4, 3] as $tailLength) {
            if ($length > $tailLength) {
                $pos = mb_strpos($text, mb_substr($name, -$tailLength));
                if ($pos !== false) {
                    return $pos;
                }
            }
        }

        return false;
    }

    /**
     * 句子里提到了本站所在的城市（不等于外地，只是不限定区县）
     */
    private function mentionsStationCity(string $norm): bool
    {
        foreach ($this->stationCities() as $city) {
            if (mb_strpos($norm, $city) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 句子里提到的外地城市（站内没收录的）
     */
    private function matchForeignCity(string $norm): ?string
    {
        $known = $this->stationCities();

        $cities = [
            '北京', '成都', '杭州', '广州', '深圳', '重庆', '西安', '南京', '武汉', '长沙',
            '苏州', '天津', '青岛', '厦门', '昆明', '哈尔滨', '济南', '郑州', '福州', '合肥',
            '南昌', '大连', '沈阳', '南宁', '贵阳', '太原', '石家庄', '兰州', '海口', '无锡',
            '宁波', '佛山', '东莞', '珠海', '温州', '常州', '徐州', '烟台', '泉州', '香港',
            '澳门', '台湾', '东京', '首尔', '曼谷', '新加坡',
        ];

        foreach ($cities as $city) {
            if (in_array($city, $known, true)) {
                continue;
            }

            if (mb_strpos($norm, $city) !== false) {
                return $city;
            }
        }

        return null;
    }

    /**
     * 从「XX怎么做」里猜出菜名，猜不出返回 null
     *
     * 只做保守判断：去掉问法后剩 2 到 8 个字、且不含泛词，才当成菜名。
     */
    private function guessDishName(string $norm): ?string
    {
        $noise = [
            '请问', '帮我', '告诉我', '我想', '想学', '学做', '教一下', '教我', '能不能', '可以教',
            '怎么做', '咋做', '如何做', '怎样做', '怎么烧', '怎么炒', '怎么蒸', '怎么炖', '怎么弄', '怎么煮',
            '的家常做法', '做法', '教程', '步骤', '配方', '详细', '具体', '一下', '需要', '要哪些食材',
            '什么食材', '哪些食材', '的正宗', '家常版', '吗', '呢', '啊', '呀', '的',
        ];

        $name = $norm;
        foreach ($noise as $word) {
            $name = str_replace($word, '', $name);
        }

        $name = preg_replace('/[\s\p{P}]+/u', '', $name) ?? '';

        if (mb_strlen($name) < 2 || mb_strlen($name) > 8) {
            return null;
        }

        foreach (['什么', '哪些', '推荐', '随便', '好吃', '几道', '一道', '哪道', '哪个', '菜谱', '家常菜'] as $word) {
            if (mb_strpos($name, $word) !== false) {
                return null;
            }
        }

        return $name;
    }

    // ==================== 各类回答 ====================

    /** 怎么做 */
    private function answerRecipe(Recipe $recipe, array $context): array
    {
        $steps = $recipe->steps()->take(2)->get();
        $ingredients = $recipe->ingredients()->get();
        $places = $recipe->restaurants()->take(2)->get();

        $lines = [
            "「{$recipe->title}」我有完整做法 —— {$recipe->category}、难度{$recipe->difficulty}，准备 {$recipe->prep_minutes} 分钟、烹饪 {$recipe->cook_minutes} 分钟，一共 {$recipe->servings} 人份。",
        ];

        if ($steps->isNotEmpty()) {
            $lines[] = "\n开头两步先把节奏立住：";
            foreach ($steps as $step) {
                $lines[] = "{$step->step_no}. ".$this->clip($step->content, 58);
            }
        }

        if ($ingredients->isNotEmpty()) {
            $names = $ingredients->take(5)->pluck('name')->implode('、');
            $more = $ingredients->count() > 5 ? ' 等' : '';
            $lines[] = "\n要用到 {$ingredients->count()} 样食材：{$names}{$more}。";
        }

        if ($places->isNotEmpty()) {
            $names = $places->pluck('name')->implode('、');
            $lines[] = "懒得动手的话，{$names}做这道菜不错。";
        }

        $quick = [
            "{$recipe->title}的食材在哪买",
            "哪家店能吃到{$recipe->title}",
            '换一道简单点的',
        ];

        return $this->make(
            implode("\n", $lines),
            [$this->recipeCard($recipe, "{$recipe->steps()->count()} 个步骤")],
            $quick,
            'recipe',
            ['recipe_id' => $recipe->id]
        );
    }

    /** 去哪吃 */
    private function answerWhereToEat(
        ?Recipe $dish,
        ?string $cuisine,
        ?string $district,
        array $price,
        array $context,
        ?array $sort = null,
        ?string $scene = null
    ): array {
        // 指定某道菜：先看哪些店有这道菜
        if ($dish) {
            $serving = $dish->restaurants()->withCount('comments')->get();
            if ($serving->isNotEmpty()) {
                $names = $serving->pluck('name')->implode('、');
                $cards = $serving->map(fn (Restaurant $r) => $this->restaurantCard($r))->values()->all();

                return $this->make(
                    "想吃现成的「{$dish->title}」，{$serving->count()} 家店做得好：{$names}。\n点开店铺能看到地址、营业时间和菜单，也可以先看看厨友的评价。",
                    $cards,
                    ["{$serving->first()->name}人均多少", '这个区的其他餐厅', '还是自己在家做'],
                    'restaurant',
                    ['recipe_id' => $dish->id]
                );
            }

            return $this->answerRecipe($dish, $context);
        }

        $query = Restaurant::withCount('comments');
        if ($district) {
            $query->where('district', 'like', "%{$district}%");
        }
        if ($cuisine) {
            $query->where('cuisine', 'like', "%{$cuisine}%");
        }
        if ($price['max']) {
            $query->where('avg_price', '<=', $price['max']);
        }
        if ($price['min']) {
            $query->where('avg_price', '>=', $price['min']);
        }

        // 问最贵／最便宜／评分最高时，直接按对应字段取前三
        if ($sort) {
            $list = (clone $query)->reorder()->orderBy($sort['field'], $sort['dir'])->take(3)->get();
        } else {
            $list = $query->orderByDesc('rating')->take(3)->get();
        }

        $desc = $this->filterLabel($cuisine, $district, $price);
        if ($scene) {
            $desc = "按「{$scene}」这个场景";
        }
        $relaxed = '';

        // 条件太窄时放宽价位再找一次
        if ($list->isEmpty() && ($price['max'] || $price['min'])) {
            $relaxed = '按你给的价位没找到，放宽度再筛了一遍。';
            $list = Restaurant::withCount('comments')
                ->when($district, fn ($q) => $q->where('district', 'like', "%{$district}%"))
                ->when($cuisine, fn ($q) => $q->where('cuisine', 'like', "%{$cuisine}%"))
                ->when($sort, fn ($q) => $q->orderBy($sort['field'], $sort['dir']),
                    fn ($q) => $q->orderByDesc('rating'))
                ->take(3)->get();
        }

        if ($list->isEmpty()) {
            return $this->make(
                "{$desc}，这几天还没收录到合适的店。要不要换个区或者换个菜系看看？",
                [],
                ['静安区有什么好吃的', '人均 100 以内的店', '看看所有餐厅'],
                'restaurant',
                $context
            );
        }

        $lines = [];
        if ($relaxed) {
            $lines[] = $relaxed;
        }

        if ($sort) {
            // 排序类问题：把第一名说清楚，后面的顺带排开
            $top = $list->first();
            $rest = $list->slice(1);
            $signature = $top->signatureDishes()->take(2)->pluck('name')->implode('、');

            $lines[] = "站里{$sort['label']}的是「{$top->name}」—— 人均 ¥{$top->avg_price}，"
                ."{$top->district}，{$top->cuisine}".($signature ? "，招牌 {$signature}" : '')
                ."｜{$top->hours}";

            if ($rest->isNotEmpty()) {
                $lines[] = "\n往下排：";
                foreach ($rest as $r) {
                    $sig = $r->signatureDishes()->take(2)->pluck('name')->implode('、');
                    $lines[] = "· {$r->name} — 人均 ¥{$r->avg_price}，{$r->district}"
                        .($sig ? "，招牌 {$sig}" : '');
                }
            }

            $lines[] = "\n点卡片能看地址、电话和菜单。";
        } else {
            $lines[] = "{$desc}，找到 {$list->count()} 家：";
            foreach ($list->values() as $i => $r) {
                $signature = $r->signatureDishes()->take(2)->pluck('name')->implode('、');
                $line = ($i + 1).". {$r->name} — 人均 ¥{$r->avg_price}，{$r->district}";
                if ($signature) {
                    $line .= "，招牌 {$signature}";
                }
                $lines[] = $line;
            }
            $lines[] = "\n点卡片能看地址、厨师和服务员名单，也能直接留言。";
        }

        return $this->make(
            implode("\n", $lines),
            $list->map(fn (Restaurant $r) => $this->restaurantCard($r))->all(),
            $sort
                ? ["{$list->first()->name}招牌菜是什么", '换个人均便宜的', '还有别的菜系吗']
                : ['换个人均便宜的', '换一家', '这个区还有别的吗'],
            'restaurant',
            ['cuisine' => $cuisine, 'district' => $district]
        );
    }

    /** 点名某家店：给地址、招牌、人均 */
    private function answerRestaurantDetail(Restaurant $restaurant, array $context): array
    {
        $signature = $restaurant->signatureDishes()->take(3)->pluck('name')->implode('、');
        $menuCount = $restaurant->menuItems()->count();
        $rating = $restaurant->average_rating ?? $restaurant->rating;

        $lines = [
            "「{$restaurant->name}」在{$restaurant->city}{$restaurant->district}{$restaurant->address}。",
            "{$restaurant->cuisine}，人均 ¥{$restaurant->avg_price}，{$restaurant->hours}营业，评分 ".number_format($rating, 1)." 分。",
        ];

        if ($signature) {
            $lines[] = "招牌菜：{$signature}。";
        }

        $lines[] = "菜单一共 {$menuCount} 道，店里有 ".$restaurant->chefs()->count().' 位厨师、'
            .$restaurant->waiters()->count().' 位服务员，点进去能看到完整名单。';

        if ($restaurant->phone) {
            $lines[] = "订位电话 {$restaurant->phone}。";
        }

        return $this->make(
            implode("\n", $lines),
            [$this->restaurantCard($restaurant)],
            array_filter([
                $signature ? mb_substr(explode('、', $signature)[0], 0, 20).'怎么做' : null,
                "{$restaurant->district}还有什么好吃的",
                '换一家更便宜的',
            ]),
            'restaurant_detail',
            ['cuisine' => $restaurant->cuisine, 'district' => $restaurant->district]
        );
    }

    /** 食材店一览 */
    private function answerSupplierList(array $context): array
    {
        $shops = Supplier::withCount('products')->orderBy('category')->get();

        if ($shops->isEmpty()) {
            return $this->make(
                '食材店还在陆续入驻，晚点再来看看。',
                [],
                $this->defaultQuick(),
                'supplier_list',
                $context
            );
        }

        $lines = ["全站 {$shops->count()} 家食材店，按主营分成几类："];
        foreach ($shops as $shop) {
            $lines[] = "· {$shop->name}（{$shop->category}）— {$shop->district}，{$shop->hours}，"
                .$shop->products_count.' 个在售品';
        }
        $lines[] = "\n想买什么直接问我「XX 在哪买」，我帮你找到具体货品和价格。";

        return $this->make(
            implode("\n", $lines),
            $shops->map(fn (Supplier $s) => $this->supplierCard($s))->all(),
            ['五花肉在哪买', '哪里买海鲜', '蔬菜哪家新鲜'],
            'supplier_list',
            $context
        );
    }

    /** 买食材 */
    private function answerIngredient(?string $ingredient, ?Recipe $dish, array $context): array
    {
        // 「这道菜的食材在哪买」：没有具体食材名时，按整道菜的用料逐样找
        if (! $ingredient && $dish) {
            return $this->answerDishShoppingList($dish, $context);
        }

        $term = $ingredient ?: '食材';
        $products = $this->matchProducts($term);
        $category = $this->guessCategory($term);

        // 用到这个食材的菜
        $dishes = $ingredient
            ? Recipe::whereHas('ingredients', fn ($q) => $q->where('name', 'like', "%{$ingredient}%"))
                ->take(3)->get()
            : collect();

        $lines = [];

        if ($products->isNotEmpty()) {
            $lines[] = "「{$term}」在食材店有货：";
            foreach ($products->take(4) as $p) {
                $lines[] = "· {$p->supplier->name} — {$p->name} ¥{$this->money($p->price)}/{$p->unit}"
                    .($p->is_signature ? '（主推）' : '');
            }

            $shop = $products->first()->supplier;
            $lines[] = "\n{$shop->name}在{$shop->city}{$shop->district}，{$shop->hours}营业，"
                .($shop->delivery ?: '支持到店选购')."。";
        } else {
            $shops = $category ? Supplier::where('category', $category)->take(3)->get() : collect();
            if ($shops->isNotEmpty()) {
                $lines[] = "「{$term}」暂时没有店单卖，不过这类货{$shops->pluck('name')->implode('、')}都有，可以问问能不能捎上。";
            } else {
                $lines[] = "「{$term}」目前还没有食材店收录，可以去食材页看看同类店铺。";
            }
        }

        if ($category && isset(self::PICKING_TIPS[$category])) {
            $lines[] = "\n挑{$category}的小经验：".self::PICKING_TIPS[$category];
        }

        if ($dishes->isNotEmpty()) {
            $lines[] = "用到它的菜：".$dishes->pluck('title')->implode('、').'（点开能看完整做法）。';
        }

        $cards = $products->take(4)->map(fn (SupplierProduct $p) => $this->productCard($p))->all();
        if (empty($cards) && $category) {
            $cards = Supplier::where('category', $category)->take(3)->get()
                ->map(fn (Supplier $s) => $this->supplierCard($s))->all();
        }

        $quick = ["{$term}怎么挑", '附近还有什么食材店'];
        if ($dishes->isNotEmpty()) {
            $quick[] = "{$dishes->first()->title}怎么做";
        }

        return $this->make(
            implode("\n", $lines),
            $cards,
            $quick,
            'ingredient',
            ['ingredient' => $term] + ($dish ? ['recipe_id' => $dish->id] : $context)
        );
    }

    /** 一道菜的完整采购清单 */
    private function answerDishShoppingList(Recipe $dish, array $context): array
    {
        $ingredients = $dish->ingredients()->pluck('name');
        $hits = [];

        foreach ($ingredients as $name) {
            $product = $this->matchProducts($name)->first();
            if ($product) {
                $hits[$name] = $product;
            }
        }

        if (! $hits) {
            return $this->make(
                "「{$dish->title}」要用的 {$ingredients->count()} 样东西，食材店暂时没有对得上的现货。\n可以去食材页按类别逛逛，或者到店问问能不能代买。",
                [],
                ['有什么食材店', "{$dish->title}怎么做"],
                'ingredient',
                ['recipe_id' => $dish->id]
            );
        }

        $count = count($hits);
        $lines = ["做「{$dish->title}」一共 {$ingredients->count()} 样，食材店能直接买到 {$count} 样："];
        foreach ($hits as $name => $product) {
            $lines[] = "· {$name}：{$product->supplier->name}的 {$product->name}，¥{$this->money($product->price)}/{$product->unit}";
        }

        $rest = $ingredients->reject(fn ($name) => isset($hits[$name]))->take(6)->implode('、');
        if ($rest) {
            $lines[] = "\n剩下的{$rest}属于常备调料，粮油行和超市都能凑齐。";
        }

        $lines[] = '点卡片可以直接看店铺地址和配送门槛。';

        return $this->make(
            implode("\n", $lines),
            collect($hits)->take(4)->map(fn (SupplierProduct $p) => $this->productCard($p))->values()->all(),
            [array_key_first($hits).'在哪买', '这家店还有什么', "{$dish->title}怎么做"],
            'ingredient',
            ['recipe_id' => $dish->id]
        );
    }


    private function answerRecipeList(string $norm, array $context, ?string $intro = null): array
    {
        $query = Recipe::query();

        // 提到口味／场景时挑对应分类
        $category = null;
        foreach (Recipe::CATEGORIES as $c) {
            if (mb_strpos($norm, $c) !== false) {
                $category = $c;
                break;
            }
        }
        if (! $category) {
            $map = ['素' => '轻食', '清淡' => '汤羹', '汤' => '汤羹', '辣' => '川菜', '下饭' => '家常菜'];
            foreach ($map as $k => $v) {
                if (mb_strpos($norm, $k) !== false) {
                    $category = $v;
                    break;
                }
            }
        }

        // 说要省事／新手，就按总耗时挑简单的
        $easy = false;
        foreach (['简单', '新手', '快', '省事', '容易', '没时间', '零基础'] as $w) {
            if (mb_strpos($norm, $w) !== false) {
                $easy = true;
                break;
            }
        }

        $query->when($category, fn ($q) => $q->where('category', $category));

        if ($easy) {
            $query->where('difficulty', '简单')->orderByRaw('prep_minutes + cook_minutes asc');
        } else {
            $query->inRandomOrder();
        }

        $list = $query->take(3)->get();

        // 条件太窄（比如「川菜里最简单的」）就放宽
        if ($list->isEmpty()) {
            $list = Recipe::when($category, fn ($q) => $q->where('category', $category))
                ->inRandomOrder()->take(3)->get();
        }

        if ($list->isEmpty()) {
            return $this->fallback($context);
        }

        if ($intro) {
            $title = $intro;
        } elseif ($easy) {
            $title = '这三道最省时间，最慢的一道半小时也能端上桌：';
        } else {
            $title = $category ? "{$category}里这几道值得先试：" : '这几道最近在食光很受欢迎：';
        }

        return $this->make(
            $title."\n每道都写了完整步骤和用量，跟着做基本不会翻车。想做哪道，直接问我「XX怎么做」也行。",
            $list->map(fn (Recipe $r) => $this->recipeCard($r))->all(),
            ['哪道最简单', '适合新手的菜', '推荐个汤'],
            'suggest',
            $context
        );
    }

    /** 闲聊与能力介绍 */
    private function chitchat(string $norm, array $context): ?array
    {
        // 做菜翻车求助
        foreach (self::FIXES as $key => $tips) {
            if (mb_strpos($norm, $key) !== false) {
                return $this->make(
                    "别急，{$key}这事谁都遇上过。两个补救办法：\n1. {$tips[0]}\n2. {$tips[1]}\n下次做之前可以把步骤看一遍再开火。",
                    [],
                    ['推荐个不容易翻车的菜', '红烧肉怎么做'],
                    'fix',
                    $context
                );
            }
        }

        $greets = ['你好', '您好', '在吗', 'hi', 'hello', '嗨', '哈喽', '早上好', '晚上好'];
        foreach ($greets as $g) {
            if ($norm === $g || mb_strpos($norm, $g) === 0) {
                return $this->make(
                    "你好呀，我是食光的美食助手。\n站里的菜谱、餐厅和食材店我都翻过一遍，可以帮你：\n· 查某道菜怎么做，几样食材、几步、多少分钟\n· 按菜系、区县、人均找餐厅，也能查哪家店做某道菜\n· 告诉你某样食材哪家店有卖、多少钱，顺便说说怎么挑\n\n想从哪儿开始？",
                    [],
                    ['红烧肉怎么做', '静安区有什么好吃的', '五花肉在哪买'],
                    'greet',
                    $context
                );
            }
        }

        if (mb_strpos($norm, '你是谁') !== false || mb_strpos($norm, '能做什么') !== false
            || mb_strpos($norm, '会什么') !== false || mb_strpos($norm, '帮我做什么') !== false) {
            $counts = Recipe::count().' 道菜谱、'.Restaurant::count().' 家餐厅、'.Supplier::count().' 家食材店';

            return $this->make(
                "我是站内的小助手，本事都来自食光自己的数据 —— {$counts}。\n\n可以这样问我：\n· 「麻婆豆腐怎么做」\n· 「徐汇区人均 200 以内有什么店」\n· 「鲈鱼哪里买」\n· 「哪家店能吃到红烧肉」\n\n问到没收录的东西我会直说，不会瞎编。",
                [],
                ['麻婆豆腐怎么做', '徐汇区有什么好吃的', '鲈鱼在哪买'],
                'about',
                $context
            );
        }

        if (mb_strpos($norm, '谢谢') !== false || mb_strpos($norm, '多谢') !== false || $norm === '好的' || $norm === 'ok') {
            return $this->make(
                "不客气～火候到了，菜自然就好吃。还想问什么随时叫我。",
                [],
                $this->defaultQuick(),
                'thanks',
                $context
            );
        }

        return null;
    }

    /** 兜底：没听懂 */
    private function fallback(array $context): array
    {
        $list = Recipe::inRandomOrder()->take(2)->get();

        return $this->make(
            "这个问题我没把握，先不瞎猜。\n能帮上忙的是这些：菜谱做法（站内的、站外的都能说）、按菜系／区县／预算／场景找餐厅、食材去哪买、怎么挑。说说你想吃哪一口？",
            $list->map(fn (Recipe $r) => $this->recipeCard($r))->all(),
            $this->defaultQuick(),
            'fallback',
            $context
        );
    }

    // ==================== 实体识别 ====================

    /** 找出问句里的菜谱 */
    private function matchDish(string $norm): ?Recipe
    {
        $recipes = $this->recipes();

        // 1. 口语别名，长的优先
        $aliasHits = [];
        foreach (self::DISH_ALIASES as $alias => $title) {
            if (mb_strpos($norm, $alias) !== false) {
                $aliasHits[$alias] = $title;
            }
        }
        if ($aliasHits) {
            uksort($aliasHits, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            $hit = $recipes->firstWhere('title', reset($aliasHits));
            if ($hit) {
                return $hit;
            }
        }

        // 2. 问句里完整包含菜名
        foreach ($recipes as $recipe) {
            if (mb_strpos($norm, $this->normalize($recipe->title)) !== false) {
                return $recipe;
            }
        }

        // 3. 最长公共子串兜底，命中六成以上才算
        $chars = mb_str_split($norm);
        $best = null;
        $bestScore = 0;
        foreach ($recipes as $recipe) {
            $title = $this->normalize($recipe->title);
            $lcs = $this->lcsLength($chars, mb_str_split($title));
            if ($lcs < 2) {
                continue;
            }
            $score = $lcs / max(mb_strlen($title), 1);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $recipe;
            }
        }

        return $bestScore >= 0.6 ? $best : null;
    }

    /** 找出问句里的食材 */
    private function matchIngredient(string $norm): ?string
    {
        foreach ($this->ingredients() as $name) {
            $n = $this->normalize($name);
            if (mb_strlen($n) >= 2 && mb_strpos($norm, $n) !== false) {
                return $name;
            }
        }

        foreach (self::INGREDIENT_ALIASES as $alias => $canonical) {
            if (mb_strpos($norm, $alias) !== false) {
                return $canonical;
            }
        }

        return null;
    }

    /** 找出问句里点名的餐厅 */
    private function matchRestaurant(string $norm): ?Restaurant
    {
        $restaurants = Restaurant::all();

        foreach ($restaurants as $restaurant) {
            $name = $this->normalize($restaurant->name);
            if (mb_strpos($norm, $name) !== false) {
                return $restaurant;
            }
        }

        // 「川味小馆」被说成「川味」这类简称，八成以上相似才算
        $chars = mb_str_split($norm);
        foreach ($restaurants as $restaurant) {
            $name = $this->normalize($restaurant->name);
            $lcs = $this->lcsLength($chars, mb_str_split($name));
            if ($lcs >= 3 && $lcs / mb_strlen($name) >= 0.6) {
                return $restaurant;
            }
        }

        return null;
    }

    private function matchCuisine(string $norm): ?string
    {
        $hits = [];
        foreach (self::CUISINE_ALIASES as $alias => $cuisine) {
            if (mb_strpos($norm, $alias) !== false) {
                $hits[$alias] = $cuisine;
            }
        }
        if (! $hits) {
            return null;
        }
        uksort($hits, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return reset($hits);
    }

    private function matchDistrict(string $norm): ?string
    {
        foreach (self::DISTRICTS as $district) {
            if (mb_strpos($norm, $district) !== false) {
                return $district;
            }
        }

        return null;
    }

    /** 解析价位：返回 ['max'=>上限, 'min'=>下限, 'label'=>说明] */
    private function parsePrice(string $norm): array
    {
        if (preg_match('/人均?(\d{2,4})/', $norm, $m) || preg_match('/(\d{2,4})(元|块|以内|以下|左右)/u', $norm, $m)) {
            $value = (int) $m[1];
            $cheap = mb_strpos($norm, '以上') !== false || mb_strpos($norm, '起') !== false;

            return $cheap
                ? ['max' => null, 'min' => $value, 'label' => "人均 {$value} 以上"]
                : ['max' => $value, 'min' => null, 'label' => "人均 {$value} 以内"];
        }

        foreach (['便宜', '实惠', '平价', '省钱', '学生党'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return ['max' => 100, 'min' => null, 'label' => '人均 100 以内'];
            }
        }

        // 「约会」「纪念日」交给场景识别处理，这里不再强行抬高价位
        foreach (['高档', '高端', '贵一点', '请客'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return ['max' => null, 'min' => 200, 'label' => '人均 200 以上'];
            }
        }

        return ['max' => null, 'min' => null, 'label' => ''];
    }

    private function wantsPurchase(string $norm): bool
    {
        foreach (['买', '购', '价格', '多少钱', '价位', '市场', '菜场', '供应', '进货', '批发', '配送', '有货', '哪有卖', '哪卖', '怎么挑', '新鲜'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    private function wantsRestaurant(string $norm): bool
    {
        foreach ([
            '餐厅', '饭店', '馆子', '哪家', '哪里吃', '去哪吃', '吃什么', '有什么好吃', '哪里好吃',
            '吃点啥', '探店', '人均', '好吃的地', '能吃到', '下馆子', '店',
            // 排序与比较
            '最贵', '最便宜', '最实惠', '最划算', '评分', '排行', '排名', '榜单', '推荐一家', '推荐个店',
            // 场景
            '约会', '聚餐', '请客', '一人食', '一个人吃', '带娃', '带小孩', '亲子', '夜宵', '宵夜',
            '下午茶', '生日', '纪念日', '商务', '包间', '安静', '氛围', '拍照好看',
        ] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    private function wantsRecipe(string $norm): bool
    {
        foreach (['怎么做', '做法', '咋做', '如何做', '怎么烧', '怎么炒', '怎么蒸', '教程', '步骤', '配方', '教我', '学做', '菜谱', '怎么弄'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    private function wantsSuggestion(string $norm): bool
    {
        foreach (['推荐', '随便', '不知道吃', '吃什么好', '有什么好吃', '来一道', '想吃', '简单', '新手', '快一点'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /** 想逛食材店 / 菜市场 */
    private function wantsSupplierList(string $norm): bool
    {
        foreach (['食材店', '供应商', '菜市场', '菜场', '买菜', '哪有卖', '上哪买'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /** 「它／这道／那呢」这类省略说法 */
    private function refersToPrevious(string $norm): bool
    {
        foreach (['它', '这道', '这个菜', '那道', '还有呢', '还有别的', '别的', '这个区', '这家', '食材', '材料', '哪买', '怎么做'] as $word) {
            if (mb_strpos($norm, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    // ==================== 数据与卡片 ====================

    /** @return Collection<int, Recipe> */
    private function recipes(): Collection
    {
        return $this->recipeCache ??= Recipe::with('ingredients')->get();
    }

    /** @return Collection<int, string> */
    private function ingredients(): Collection
    {
        return $this->ingredientCache ??= Recipe::query()
            ->join('recipe_ingredients', 'recipe_ingredients.recipe_id', '=', 'recipes.id')
            ->distinct()
            ->pluck('recipe_ingredients.name')
            ->sortByDesc(fn ($n) => mb_strlen($n))
            ->values();
    }

    /** 在售商品按相关度排序 */
    private function matchProducts(string $term): Collection
    {
        $termNorm = $this->normalize($term);
        if (mb_strlen($termNorm) < 2) {
            return collect();
        }

        $category = $this->guessCategory($term);
        $chars = mb_str_split($termNorm);

        return SupplierProduct::with('supplier')->get()
            ->map(function (SupplierProduct $p) use ($termNorm, $chars, $category) {
                $name = $this->normalize($p->name);
                $score = 0;

                if (mb_strpos($name, $termNorm) !== false) {
                    $score = 100;
                } elseif (mb_strpos($termNorm, $name) !== false) {
                    $score = 90;
                } else {
                    $lcs = $this->lcsLength($chars, mb_str_split($name));
                    if ($lcs >= 2) {
                        $score = 40 + $lcs * 8;
                    }
                }

                if ($score > 0 && $category && $p->category === $category) {
                    $score += 10;
                }

                return ['product' => $p, 'score' => $score];
            })
            ->filter(fn ($row) => $row['score'] >= 48)
            ->sortByDesc('score')
            ->take(4)
            ->pluck('product')
            ->values();
    }

    private function guessCategory(string $term): ?string
    {
        foreach (self::CATEGORY_HINTS as $category => $words) {
            foreach ($words as $word) {
                if (mb_strpos($term, $word) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }

    private function recipeCard(Recipe $recipe, ?string $tag = null): array
    {
        $meta = "{$recipe->category} · {$recipe->total_minutes} 分钟 · {$recipe->difficulty}";

        return [
            'type' => 'recipe',
            'title' => $recipe->title,
            'meta' => $meta,
            'image' => $recipe->cover,
            'url' => route('recipes.show', $recipe),
            'tag' => $tag,
        ];
    }

    private function restaurantCard(Restaurant $restaurant): array
    {
        $count = $restaurant->comments_count ?? $restaurant->comments()->count();
        $rating = $restaurant->average_rating ?? $restaurant->rating;

        return [
            'type' => 'restaurant',
            'title' => $restaurant->name,
            'meta' => "{$restaurant->cuisine} · 人均 ¥{$restaurant->avg_price} · ".number_format($rating, 1)." 分 · {$count} 条评价",
            'image' => $restaurant->cover,
            'url' => route('restaurants.show', $restaurant),
            'tag' => $restaurant->district,
        ];
    }

    private function supplierCard(Supplier $supplier): array
    {
        return [
            'type' => 'supplier',
            'title' => $supplier->name,
            'meta' => "{$supplier->category} · {$supplier->district} · {$supplier->hours}",
            'image' => $supplier->cover,
            'url' => route('suppliers.show', $supplier),
            'tag' => $supplier->category,
        ];
    }

    private function productCard(SupplierProduct $product): array
    {
        return [
            'type' => 'product',
            'title' => $product->name.' · ¥'.$this->money($product->price).'/'.$product->unit,
            'meta' => "{$product->supplier->name} · {$product->supplier->district}",
            'image' => $product->supplier->cover,
            'url' => route('suppliers.show', $product->supplier),
            'tag' => $product->is_signature ? '主推' : null,
        ];
    }

    // ==================== 小工具 ====================

    private function make(string $content, array $cards, array $quick, string $intent, array $context): array
    {
        // 只留必要的上下文，避免越滚越大
        $context = array_intersect_key($context, array_flip(['recipe_id', 'cuisine', 'district', 'ingredient']));

        return [
            'content' => $content,
            'cards' => array_values($cards),
            'quick' => array_values(array_unique($quick)),
            'intent' => $intent,
            'context' => $context,
        ];
    }

    private function defaultQuick(): array
    {
        return ['红烧肉怎么做', '静安区有什么好吃的', '五花肉在哪买'];
    }

    private function filterLabel(?string $cuisine, ?string $district, array $price): string
    {
        $parts = array_filter([$district, $cuisine, $price['label']]);
        if (! $parts) {
            return '全站评价最高的几家';
        }

        return '按「'.implode(' · ', $parts).'」筛了一遍';
    }

    private function money(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function clip(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length).'…' : $text;
    }

    /** 去空格与标点，便于匹配 */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[\s\x{3000}]+/u', '', $text);

        return preg_replace('/[，。！？、,.!?;；:："\'“”‘’（）()《》【】~—\-…]+/u', '', $text);
    }

    /** 最长公共子串长度 */
    private function lcsLength(array $a, array $b): int
    {
        $n = count($a);
        $m = count($b);
        $best = 0;
        $prev = array_fill(0, $m + 1, 0);

        for ($i = 1; $i <= $n; $i++) {
            $cur = array_fill(0, $m + 1, 0);
            for ($j = 1; $j <= $m; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $cur[$j] = $prev[$j - 1] + 1;
                    if ($cur[$j] > $best) {
                        $best = $cur[$j];
                    }
                }
            }
            $prev = $cur;
        }

        return $best;
    }
}
