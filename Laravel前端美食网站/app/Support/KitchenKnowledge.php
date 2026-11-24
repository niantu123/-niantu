<?php

namespace App\Support;

use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

/**
 * 把站内菜谱 / 餐厅 / 食材店整理成给大模型的资料段落。
 *
 * 站内资料负责「具体数字」——价格、地址、营业时间绝不瞎编；
 * 站外菜的做法、技巧则交给模型的通用美食知识，并要在回答里点明出处。
 * 资料 10 分钟缓存一次，后台改了数据最多等这么久生效。
 */
class KitchenKnowledge
{
    private const CACHE_KEY = 'assistant.knowledge.v1';

    private const PERSONA = <<<'TXT'
你是「食光」家常菜谱社区的美食助手，跟用户说话像熟悉厨房的朋友：口语、亲切、不客套。你既熟悉站内收录的内容，也懂各类菜式的通用做法。

【两类知识，必须分清】
A. 站内资料（下面列出的菜谱、餐厅、食材店）：这里的具体数字是唯一可信来源 —— 菜名、总耗时、难度、人均、地址、电话、营业时间、单价、招牌菜、有没有收录某家店某道菜。凡涉及这些数字，只能照抄资料，绝不推测。
B. 通用美食知识：资料里没有的菜怎么做、食材怎么处理、火候与调味的道理、菜系特点、营养搭配、厨房小技巧、饮食文化，这些都可以正常回答，用常识讲就行。这类内容开头点明「站里没收录，我按常见做法说」，让用户知道这不是站内菜谱。

【回答规则】
1. 站内有的，用站内数据答；站内没有但属于美食范畴的，用通用知识答并说明出处。两类都不要编造具体的店名地址电话。
2. 问做法：给菜名、大概耗时、难度，再给 2 到 4 个关键步骤；家常菜可以顺带点出最容易翻车的那一步。
3. 问去哪吃：优先推站内同城或同菜系的餐厅，给店名、所在区、人均、招牌菜。用户想要站外或外地餐厅时，说清食光没收录，再按城市和菜系给类型建议或公认的老牌，明确声明「这些是站外推荐，食光的探店页没有」。
4. 问买食材：站内有货就给店名、商品、单价和挑选建议；站内没有这类食材店，就说没收录，再讲怎么挑、去哪种市场找。
5. 排序类问题（最贵、最便宜、评分最高、人气最旺）：直接用资料里的数字比一比，把第一名讲清楚（人均、区、招牌菜、营业时间），再顺带把后面几名排开。别含糊其辞。
6. 场景类问题（约会、聚餐、请客、夜宵、一个人吃、带娃、下午茶）：结合人均、菜系、营业时间、招牌菜给建议，并说清为什么适合这个场景。
7. 信息不够时别反问一堆：先按最可能的情况给出建议，结尾再追问一句（比如几个人、大概什么预算），把选择权留给用户。
8. 站内完全对不上、又不属于吃喝话题的，礼貌说只懂美食。
9. 说到「几家店／几道菜／几样食材」时，按资料逐条数准确；拿不准就别写数字，直接把店名列出来。
10. 问能不能订位、要不要预约：看资料里那家店标的是「可在线预约」还是「不接在线预约」。能约的告诉对方去餐厅详情页点「选日期预约」，能选日期、时段和人数；不能约的直说没有在线预约，建议按资料里的电话打过去。别承诺「我帮你订」。
11. 控制在 320 字以内，说人话。不要用 Markdown 标题和星号加粗；列举时用「·」开头，每行一条。
TXT;

    /**
     * 系统提示词：人设 + 站内资料
     */
    public function systemPrompt(): string
    {
        $context = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(10),
            fn () => $this->buildContext()
        );

        return self::PERSONA."\n\n站内资料：\n".$context;
    }

    /**
     * 资料正文
     */
    public function buildContext(): string
    {
        $blocks = [];

        $recipes = Recipe::with(['ingredients', 'steps'])->get();
        if ($recipes->isNotEmpty()) {
            $lines = ['【菜谱】共 '.$recipes->count().' 道'];
            foreach ($recipes as $recipe) {
                $ingredients = $recipe->ingredients
                    ->map(fn ($i) => trim($i->name.' '.$i->amount))
                    ->implode('、');
                $steps = $recipe->steps->take(3)
                    ->map(fn ($s) => '第'.$s->step_no.'步 '.$this->clip($s->content, 46))
                    ->implode('；');

                $lines[] = "- {$recipe->title}｜{$recipe->category}｜{$recipe->difficulty}｜"
                    ."共 {$recipe->total_minutes} 分钟｜{$recipe->servings} 人份｜".
                    "食材：{$ingredients}｜{$steps}";
            }
            $blocks[] = implode("\n", $lines);
        }

        $restaurants = Restaurant::with('signatureDishes')->get();
        if ($restaurants->isNotEmpty()) {
            // 区县分布直接数好写上：模型自己数数常出错，让它照抄
            $byDistrict = $restaurants->groupBy(fn (Restaurant $r) => $r->district ?: '未标注区')
                ->map(fn ($group, $district) => $district.' '.$group->count().' 家')
                ->implode('、');

            $lines = [
                '【餐厅】共 '.$restaurants->count().' 家',
                '（区县分布：'.$byDistrict.'。客人问某个区有几家，直接照抄这里的数字，不要自己数。）',
            ];
            foreach ($restaurants as $restaurant) {
                $signature = $restaurant->signatureDishes->take(3)->pluck('name')->implode('、');
                $lines[] = "- {$restaurant->name}｜{$restaurant->cuisine}｜{$restaurant->city}{$restaurant->district}｜"
                    ."人均 {$restaurant->avg_price} 元｜{$restaurant->hours}｜评分 ".number_format($restaurant->rating, 1)
                    ."｜招牌：{$signature}｜地址：{$restaurant->address}"
                    ."｜".($restaurant->accepts_booking ? '可在线预约' : '不接在线预约')
                    .($restaurant->phone ? "｜电话：{$restaurant->phone}" : '');
            }
            $blocks[] = implode("\n", $lines);
        }

        $suppliers = Supplier::with('products')->get();
        if ($suppliers->isNotEmpty()) {
            $lines = ['【食材店】共 '.$suppliers->count().' 家'];
            foreach ($suppliers as $supplier) {
                $products = $supplier->products->take(6)
                    ->map(fn ($p) => "{$p->name} {$p->price} 元/{$p->unit}")
                    ->implode('、');
                $lines[] = "- {$supplier->name}｜主营{$supplier->category}｜{$supplier->district}｜"
                    ."{$supplier->hours}｜".($supplier->delivery ?: '到店选购')
                    ."｜在售：{$products}".($supplier->phone ? "｜电话：{$supplier->phone}" : '');
            }
            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    private function clip(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length).'…' : $text;
    }
}
