<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = User::ROLE_USER, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => '测试用户',
            'email' => 'u'.uniqid().'@shiguang.test',
            'password' => Hash::make('abcd1234'),
            'role' => $role,
        ], $extra));
    }

    private function makeRecipe(array $extra = []): Recipe
    {
        return Recipe::create(array_merge([
            'title' => '测试菜',
            'category' => '家常菜',
            'cover' => '/images/recipes/hong-shao-rou.png',
            'intro' => '测试用菜谱简介。',
            'prep_minutes' => 5,
            'cook_minutes' => 10,
            'difficulty' => '简单',
            'servings' => 2,
        ], $extra));
    }

    private function makeRestaurant(?int $ownerId = null): Restaurant
    {
        return Restaurant::create([
            'owner_id' => $ownerId,
            'name' => '测试小馆',
            'city' => '上海',
            'district' => '静安区',
            'address' => '测试路 1 号',
            'hours' => '11:00–21:00',
            'avg_price' => 80,
            'rating' => 4.5,
            'cuisine' => '本帮菜',
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的餐厅介绍，够十个字了。',
            'features' => ['可预订'],
        ]);
    }

    public function test_registering_as_restaurant_admin_saves_role_and_redirects_to_restaurant_admin(): void
    {
        $this->post('/register', [
            'name' => '新店长',
            'email' => 'boss2@shiguang.test',
            'password' => 'abcd1234',
            'password_confirmation' => 'abcd1234',
            'role' => 'restaurant_admin',
            'agree' => '1',
        ])->assertRedirect(route('account.restaurant'));

        $this->assertDatabaseHas('users', [
            'email' => 'boss2@shiguang.test',
            'role' => 'restaurant_admin',
        ]);
    }

    public function test_register_cannot_choose_admin_role(): void
    {
        $this->post('/register', [
            'name' => '想当管理员',
            'email' => 'fakeadmin@shiguang.test',
            'password' => 'abcd1234',
            'password_confirmation' => 'abcd1234',
            'role' => 'admin',
            'agree' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'fakeadmin@shiguang.test']);
    }

    public function test_user_can_update_email_phone_and_bio(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'newmail@shiguang.test',
            'phone' => '13800138000',
            'bio' => '爱做饭的人',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'newmail@shiguang.test',
            'phone' => '13800138000',
            'bio' => '爱做饭的人',
        ]);
    }

    public function test_phone_must_be_valid_and_unique(): void
    {
        $other = $this->makeUser(User::ROLE_USER, ['phone' => '13900139000']);
        $user = $this->makeUser();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '13900139000',
        ])->assertSessionHasErrors('phone');

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '12345',
        ])->assertSessionHasErrors('phone');
    }

    public function test_user_can_rename_account_and_it_syncs_author_name(): void
    {
        $user = $this->makeUser();
        $recipe = $this->makeRecipe(['user_id' => $user->id, 'author' => $user->name]);

        DB::table('comments')->insert([
            'recipe_id' => $recipe->id,
            'user_id' => $user->id,
            'nickname' => $user->name,
            'rating' => 5,
            'content' => '改名前的留言',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => '厨房小陈',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => '厨房小陈']);
        // 菜谱署名和留言昵称里的旧名字要一起换掉
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'author' => '厨房小陈']);
        $this->assertDatabaseHas('comments', ['user_id' => $user->id, 'nickname' => '厨房小陈']);
    }

    public function test_rename_rejects_taken_or_invalid_account_name(): void
    {
        $this->makeUser(User::ROLE_USER, ['name' => '已占用的名字']);
        $user = $this->makeUser();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => '已占用的名字',
            'email' => $user->email,
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => '带 空格',
            'email' => $user->email,
        ])->assertSessionHasErrors('name');

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'a',
            'email' => $user->email,
        ])->assertSessionHasErrors('name');
    }

    public function test_user_can_keep_own_name_when_saving_profile(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'bio' => '原名保存不算重名',
        ])->assertSessionHasNoErrors();
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'abcd1234',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertSessionHasNoErrors();

        $fresh = DB::table('users')->where('id', $user->id)->first();
        $this->assertTrue(Hash::check('newpass123', $fresh->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'wrongpass',
            'password' => 'newpass123',
            'password_confirmation' => 'newpass123',
        ])->assertSessionHasErrors('current_password');

        $fresh = DB::table('users')->where('id', $user->id)->first();
        $this->assertTrue(Hash::check('abcd1234', $fresh->password));
    }

    public function test_favorite_can_be_toggled(): void
    {
        $user = $this->makeUser();
        $recipe = $this->makeRecipe();

        $this->actingAs($user)->post(route('favorites.toggle', $recipe));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'recipe_id' => $recipe->id]);

        $this->actingAs($user)->post(route('favorites.toggle', $recipe));
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'recipe_id' => $recipe->id]);
    }

    public function test_user_can_upload_a_work_with_ingredients_and_steps(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('account.works.store'), [
            'title' => '番茄炒蛋',
            'category' => '家常菜',
            'intro' => '番茄先出汁鸡蛋后回锅，很下饭。',
            'prep_minutes' => 5,
            'cook_minutes' => 10,
            'difficulty' => '简单',
            'servings' => 2,
            'ingredients' => "番茄 2 个\n鸡蛋 3 个",
            'steps' => "番茄切块\n鸡蛋炒到七成熟\n一起翻炒出锅",
        ])->assertRedirect();

        $recipe = Recipe::where('title', '番茄炒蛋')->first();

        $this->assertNotNull($recipe);
        $this->assertSame($user->id, $recipe->user_id);
        $this->assertSame($user->name, $recipe->author);
        $this->assertSame('/images/recipes/default-cover.svg', $recipe->cover);
        $this->assertSame(2, $recipe->ingredients()->count());
        $this->assertSame(3, $recipe->steps()->count());
        $this->assertSame('2 个', $recipe->ingredients()->where('name', '番茄')->value('amount'));
    }

    public function test_user_can_delete_only_own_work(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $recipe = $this->makeRecipe(['user_id' => $owner->id]);

        $this->actingAs($other)->delete(route('account.works.destroy', $recipe))->assertForbidden();
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);

        $this->actingAs($owner)->delete(route('account.works.destroy', $recipe))->assertRedirect(route('account.works'));
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_restaurant_comment_is_saved_to_comments_table(): void
    {
        $user = $this->makeUser();
        $restaurant = $this->makeRestaurant();

        $this->actingAs($user)->post(route('comments.restaurant.store', $restaurant), [
            'rating' => 4,
            'content' => '红烧肉不错，环境安静，适合带家里人。',
        ])->assertRedirect(route('restaurants.show', $restaurant).'#comments');

        $this->assertDatabaseHas('comments', [
            'restaurant_id' => $restaurant->id,
            'recipe_id' => null,
            'user_id' => $user->id,
            'rating' => 4,
        ]);
    }

    public function test_restaurant_admin_can_create_restaurant_and_add_menu(): void
    {
        $boss = $this->makeUser(User::ROLE_RESTAURANT);

        $this->actingAs($boss)->post(route('account.restaurant.save'), [
            'name' => '我的小馆',
            'city' => '上海',
            'district' => '徐汇区',
            'address' => '某路 20 号',
            'hours' => '11:00–21:00',
            'avg_price' => 90,
            'cuisine' => '本帮菜',
            'intro' => '一家开在弄堂口的小馆子，做的是家常本帮菜。',
            'features' => '可预订、有包间',
        ])->assertRedirect(route('account.restaurant'));

        $restaurant = Restaurant::where('name', '我的小馆')->first();
        $this->assertSame($boss->id, $restaurant->owner_id);
        $this->assertSame(['可预订', '有包间'], $restaurant->features);

        $this->actingAs($boss)->post(route('account.restaurant.menu.store'), [
            'name' => '红烧肉',
            'category' => '主菜',
            'price' => 68,
            'intro' => '每日限量',
            'is_signature' => 1,
        ])->assertRedirect(route('account.restaurant'));

        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => '红烧肉',
            'is_signature' => 1,
        ]);
    }

    public function test_restaurant_admin_cannot_touch_another_restaurant(): void
    {
        $owner = $this->makeUser(User::ROLE_RESTAURANT);
        $intruder = $this->makeUser(User::ROLE_RESTAURANT);
        $restaurant = $this->makeRestaurant($owner->id);

        $item = $restaurant->menuItems()->create([
            'name' => '别人的菜',
            'category' => '主菜',
            'price' => 48,
        ]);

        $this->actingAs($intruder)
            ->delete(route('account.restaurant.menu.destroy', $item))
            ->assertForbidden();

        $this->assertDatabaseHas('menu_items', ['id' => $item->id]);
    }

    public function test_normal_user_cannot_open_restaurant_admin(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('account.restaurant'))->assertForbidden();
    }

    public function test_work_category_lists_only_user_uploaded_recipes(): void
    {
        $user = $this->makeUser();
        $official = $this->makeRecipe(['title' => '官方菜']);
        $work = $this->makeRecipe(['title' => '厨友菜', 'user_id' => $user->id]);

        $this->get(route('recipes.index', ['category' => Recipe::USER_CATEGORY]))
            ->assertOk()
            ->assertSee('厨友菜')
            ->assertDontSee('官方菜');
    }
}
