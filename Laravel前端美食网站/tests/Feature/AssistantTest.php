<?php

namespace Tests\Feature;

use App\Models\AssistantMessage;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    private function makeRecipe(array $extra = []): Recipe
    {
        return Recipe::create(array_merge([
            'title' => '本帮红烧肉',
            'category' => '家常菜',
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的红烧肉介绍，写得足够长一点。',
            'prep_minutes' => 15,
            'cook_minutes' => 60,
            'difficulty' => '简单',
            'servings' => 3,
            'author' => '食光编辑部',
        ], $extra));
    }

    private function makeRestaurant(array $extra = []): Restaurant
    {
        return Restaurant::create(array_merge([
            'name' => '弄堂里·本帮菜馆',
            'city' => '上海',
            'district' => '静安区',
            'address' => '进贤路 128 号',
            'hours' => '11:00–21:00',
            'avg_price' => 128,
            'rating' => 4.7,
            'cuisine' => '本帮菜',
            'cover' => '/images/restaurants/dining-hall.jpg',
            'intro' => '测试用的餐厅介绍，写得足够长一点。',
        ], $extra));
    }

    private function makeSupplierWithProduct(): Supplier
    {
        $supplier = Supplier::create([
            'name' => '阿芬鲜肉铺',
            'city' => '上海',
            'district' => '黄浦区',
            'address' => '菜场 8 号摊',
            'hours' => '06:00–18:30',
            'category' => '肉禽',
            'rating' => 4.8,
            'cover' => '/images/restaurants/hall-booths.jpg',
            'intro' => '测试用的食材店介绍，写得足够长。',
        ]);

        SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'name' => '黑猪五花',
            'category' => '肉禽',
            'price' => 42,
            'unit' => '500g',
            'intro' => '三层分明',
            'is_signature' => true,
            'sort' => 0,
        ]);

        return $supplier;
    }

    private function ask(string $message)
    {
        return $this->postJson('/assistant', ['message' => $message]);
    }

    public function test_assistant_page_renders_for_guest(): void
    {
        $this->get('/assistant')
            ->assertOk()
            ->assertSeeText('美食助手')
            ->assertSeeText('食光小助手');
    }

    public function test_recipe_question_returns_recipe_answer_and_stores_history(): void
    {
        $this->makeRecipe();

        $this->ask('红烧肉怎么做')
            ->assertOk()
            ->assertJsonPath('reply.cards.0.type', 'recipe')
            ->assertJsonPath('reply.cards.0.title', '本帮红烧肉');

        $this->assertSame(2, AssistantMessage::count());
        $this->assertSame(
            1,
            AssistantMessage::where('role', 'assistant')->whereNotNull('cards')->count()
        );
    }

    public function test_restaurant_question_filters_by_district(): void
    {
        $this->makeRestaurant();

        $response = $this->ask('静安区有什么好吃的')
            ->assertOk();

        $content = $response->json('reply.content');
        $this->assertStringContainsString('弄堂里·本帮菜馆', $content);
        $this->assertStringContainsString('找到 1 家', $content);
        $this->assertSame('restaurant', $response->json('reply.cards.0.type'));
    }

    public function test_ingredient_question_points_to_supplier_product(): void
    {
        $this->makeSupplierWithProduct();

        $response = $this->ask('五花肉在哪买')
            ->assertOk();

        $this->assertStringContainsString('黑猪五花', $response->json('reply.content'));
        $this->assertStringContainsString('阿芬鲜肉铺', $response->json('reply.cards.0.meta'));
        $this->assertSame('product', $response->json('reply.cards.0.type'));
    }

    public function test_superlative_questions_rank_restaurants(): void
    {
        $this->makeRestaurant(['name' => '鮨·小野', 'avg_price' => 480, 'rating' => 4.7]);
        $this->makeRestaurant(['name' => '面屋十番', 'avg_price' => 52, 'rating' => 4.6, 'district' => '长宁区']);
        $this->makeRestaurant(['name' => '甜舍 Patisserie', 'avg_price' => 68, 'rating' => 4.9]);

        // 最贵 → 卡片与文字都要落在 480 那家
        $top = $this->ask('最贵的餐厅在哪里')->assertOk();
        $this->assertSame('鮨·小野', $top->json('reply.cards.0.title'));
        $this->assertStringContainsString('人均最高', $top->json('reply.content'));

        // 最便宜 → 52 那家
        $cheap = $this->ask('哪家店最便宜')->assertOk();
        $this->assertSame('面屋十番', $cheap->json('reply.cards.0.title'));

        // 评分最高 → 4.9 那家
        $rated = $this->ask('评分最高的餐厅')->assertOk();
        $this->assertSame('甜舍 Patisserie', $rated->json('reply.cards.0.title'));
    }

    public function test_scene_question_returns_candidates(): void
    {
        $this->makeRestaurant(['name' => '鮨·小野', 'avg_price' => 480, 'rating' => 4.7]);
        $this->makeRestaurant(['name' => '甜舍 Patisserie', 'avg_price' => 68, 'rating' => 4.9]);

        $response = $this->ask('适合约会的餐厅')->assertOk();

        // 约会不该被当成「人均 200 以上」的硬门槛，便宜的店也该进候选
        $titles = collect($response->json('reply.cards'))->pluck('title')->all();
        $this->assertContains('甜舍 Patisserie', $titles);
        $this->assertStringContainsString('约会', $response->json('reply.content'));
    }

    public function test_unknown_question_falls_back_without_inventing(): void
    {
        $this->makeRecipe();

        $response = $this->ask('今天天气怎么样')
            ->assertOk();

        // 本地兜底不编造，只说明能帮上什么
        $this->assertStringContainsString('没把握', $response->json('reply.content'));
    }

    public function test_guest_and_user_histories_are_separate(): void
    {
        $user = User::create([
            'name' => '聊天用户',
            'email' => 'chat@shiguang.test',
            'password' => bcrypt('abcd1234'),
            'role' => User::ROLE_USER,
        ]);

        $this->makeRecipe();

        $this->ask('红烧肉怎么做');
        $this->assertSame(2, AssistantMessage::whereNull('user_id')->count());

        $this->actingAs($user)->ask('红烧肉怎么做');
        $this->assertSame(2, AssistantMessage::where('user_id', $user->id)->count());

        // 游客的记录不受登录用户影响
        $this->assertSame(2, AssistantMessage::whereNull('user_id')->count());
    }

    public function test_history_page_shows_previous_messages_for_user(): void
    {
        $user = User::create([
            'name' => '聊天用户',
            'email' => 'chat2@shiguang.test',
            'password' => bcrypt('abcd1234'),
            'role' => User::ROLE_USER,
        ]);

        $this->makeRecipe();
        $this->actingAs($user)->ask('红烧肉怎么做');

        $this->actingAs($user)->get('/assistant')
            ->assertOk()
            ->assertSeeText('红烧肉怎么做');
    }

    public function test_clear_deletes_current_history(): void
    {
        $user = User::create([
            'name' => '清空用户',
            'email' => 'clear@shiguang.test',
            'password' => bcrypt('abcd1234'),
            'role' => User::ROLE_USER,
        ]);

        $this->makeRecipe();
        $this->actingAs($user)->ask('红烧肉怎么做');
        $this->assertSame(2, AssistantMessage::where('user_id', $user->id)->count());

        $this->actingAs($user)->deleteJson('/assistant')->assertOk();
        $this->assertSame(0, AssistantMessage::where('user_id', $user->id)->count());
    }

    public function test_message_is_required_and_limited(): void
    {
        $this->postJson('/assistant', ['message' => ''])->assertStatus(422);

        $this->postJson('/assistant', ['message' => str_repeat('好', 301)])->assertStatus(422);

        $this->assertSame(0, AssistantMessage::count());
    }

    public function test_uses_deepseek_when_key_configured(): void
    {
        config(['services.deepseek.key' => 'sk-test']);
        $this->makeRecipe();

        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => "红烧肉先冷水焯一遍，再小火焖 40 分钟。\n· 糖色炒到枣红色就下肉"],
                ]],
            ]),
        ]);

        $response = $this->ask('红烧肉怎么做')->assertOk();

        $this->assertSame('deepseek', $response->json('reply.source'));
        $this->assertStringContainsString('焖 40 分钟', $response->json('reply.content'));
        // 文字交给模型，卡片仍来自站内数据
        $this->assertSame('recipe', $response->json('reply.cards.0.type'));

        $this->assertSame(
            'deepseek',
            AssistantMessage::where('role', 'assistant')->value('source')
        );

        Http::assertSent(function ($request) {
            $messages = $request['messages'];

            return str_contains($request->url(), '/chat/completions')
                && $request['model'] === 'deepseek-chat'
                && $messages[0]['role'] === 'system'
                && str_contains($messages[0]['content'], '本帮红烧肉')
                && str_contains($messages[0]['content'], '站内资料')
                && end($messages)['content'] === '红烧肉怎么做';
        });
    }

    public function test_falls_back_to_local_when_deepseek_fails(): void
    {
        config(['services.deepseek.key' => 'sk-test']);
        $this->makeRecipe();

        Http::fake([
            'api.deepseek.com/*' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $response = $this->ask('红烧肉怎么做')->assertOk();

        $this->assertSame('local', $response->json('reply.source'));
        $this->assertStringContainsString('本帮红烧肉', $response->json('reply.content'));
    }

    public function test_does_not_call_api_without_key(): void
    {
        config(['services.deepseek.key' => null]);
        $this->makeRecipe();
        Http::fake();

        $response = $this->ask('红烧肉怎么做')->assertOk();

        $this->assertSame('local', $response->json('reply.source'));
        Http::assertNothingSent();
    }

    public function test_offsite_dish_is_answered_without_misleading_cards(): void
    {
        config(['services.deepseek.key' => 'sk-test']);
        $this->makeRecipe();

        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => "站里没收录这道，我按常见做法说：\n· 鸡腿肉切丁，生抽料酒淀粉腌 10 分钟"],
                ]],
            ]),
        ]);

        $response = $this->ask('宫保鸡丁怎么做')->assertOk();

        $this->assertSame('deepseek', $response->json('reply.source'));
        $this->assertStringContainsString('站里没收录', $response->json('reply.content'));
        // 站外的菜不该硬塞站内随机菜谱卡片
        $this->assertSame([], $response->json('reply.cards'));

        // 系统提示词要允许用通用美食知识，并要求点明站内没收录
        Http::assertSent(function ($request) {
            $system = $request['messages'][0]['content'];

            return str_contains($system, '通用美食知识')
                && str_contains($system, '站里没收录');
        });
    }

    public function test_foreign_city_is_answered_as_not_collected(): void
    {
        config(['services.deepseek.key' => 'sk-test']);
        $this->makeRestaurant();

        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => '食光的店都在上海，成都的没收录，站外按川菜方向给你说说。'],
                ]],
            ]),
        ]);

        $response = $this->ask('成都有什么好吃的川菜')->assertOk();

        $this->assertSame('deepseek', $response->json('reply.source'));
        $this->assertSame([], $response->json('reply.cards'));
    }

    public function test_local_mode_admits_dish_not_collected(): void
    {
        config(['services.deepseek.key' => null]);
        $this->makeRecipe();
        Http::fake();

        $response = $this->ask('宫保鸡丁怎么做')->assertOk();

        $this->assertSame('local', $response->json('reply.source'));
        $this->assertStringContainsString('还没收录', $response->json('reply.content'));
        $this->assertStringContainsString('宫保鸡丁', $response->json('reply.content'));
    }

    public function test_page_shows_deepseek_mode_when_enabled(): void
    {
        config(['services.deepseek.key' => 'sk-test', 'services.deepseek.model' => 'deepseek-chat']);

        $this->get('/assistant')
            ->assertOk()
            ->assertSeeText('DeepSeek 在线')
            ->assertSeeText('deepseek-chat');
    }
}
