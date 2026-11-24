<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_can_be_rendered(): void
    {
        $this->get('/register')->assertOk()->assertSee('创建你的账号');
    }

    public function test_login_page_can_be_rendered(): void
    {
        $this->get('/login')->assertOk()->assertSee('欢迎回来');
    }

    public function test_user_can_register_and_row_is_written_to_database(): void
    {
        $response = $this->post('/register', [
            'name' => '测试厨友',
            'email' => 'new@shiguang.test',
            'password' => 'abcd1234',
            'password_confirmation' => 'abcd1234',
            'role' => 'user',
            'agree' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = DB::table('users')->where('email', 'new@shiguang.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('测试厨友', $user->name);

        // 入库的必须是哈希后的密码，不能是明文
        $this->assertNotSame('abcd1234', $user->password);
        $this->assertTrue(Hash::check('abcd1234', $user->password));
    }

    public function test_six_character_password_is_accepted(): void
    {
        $this->post('/register', [
            'name' => '六位密码',
            'email' => 'six@shiguang.test',
            'password' => '123456',
            'password_confirmation' => '123456',
            'role' => 'user',
            'agree' => '1',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', ['email' => 'six@shiguang.test']);
    }

    public function test_logged_in_user_can_post_comment(): void
    {
        $user = User::create([
            'name' => '留言用户',
            'email' => 'c@shiguang.test',
            'password' => Hash::make('12345678'),
        ]);

        $recipe = Recipe::create([
            'title' => '测试菜',
            'category' => '家常菜',
            'cover' => '/images/recipes/hong-shao-rou.png',
            'intro' => '测试用菜谱。',
            'prep_minutes' => 5,
            'cook_minutes' => 10,
            'difficulty' => '简单',
            'servings' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('comments.store', $recipe), [
                'rating' => 5,
                'content' => '照着做了一遍，糖色炒得很成功。',
            ])
            ->assertRedirect(route('recipes.show', $recipe).'#comments');

        $this->assertDatabaseHas('comments', [
            'recipe_id' => $recipe->id,
            'nickname' => '留言用户',
            'content' => '照着做了一遍，糖色炒得很成功。',
        ]);
    }

    public function test_guest_cannot_post_comment(): void
    {
        $recipe = Recipe::create([
            'title' => '测试菜二',
            'category' => '家常菜',
            'cover' => '/images/recipes/hong-shao-rou.png',
            'intro' => '测试用菜谱。',
            'prep_minutes' => 5,
            'cook_minutes' => 10,
            'difficulty' => '简单',
            'servings' => 2,
        ]);

        $this->post(route('comments.store', $recipe), [
            'rating' => 5,
            'content' => '游客留言',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        DB::table('users')->insert([
            'name' => '已存在',
            'email' => 'taken@shiguang.test',
            'password' => Hash::make('abcd1234'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/register', [
            'name' => '另一个人',
            'email' => 'taken@shiguang.test',
            'password' => 'abcd1234',
            'password_confirmation' => 'abcd1234',
            'role' => 'user',
            'agree' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, DB::table('users')->where('email', 'taken@shiguang.test')->count());
    }

    public function test_register_requires_matching_password_and_agreement(): void
    {
        $this->post('/register', [
            'name' => '甲',
            'email' => 'bad@shiguang.test',
            'password' => 'abcd1234',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'password', 'agree']);

        $this->assertGuest();
        $this->assertSame(0, DB::table('users')->where('email', 'bad@shiguang.test')->count());
    }

    public function test_user_can_login_with_correct_password(): void
    {
        DB::table('users')->insert([
            'name' => '演示厨友',
            'email' => 'demo@shiguang.test',
            'password' => Hash::make('12345678'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'account' => 'demo@shiguang.test',
            'password' => '12345678',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_account_name(): void
    {
        DB::table('users')->insert([
            'name' => 'admin',
            'email' => 'admin@shiguang.test',
            'password' => Hash::make('123456'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'account' => 'admin',
            'password' => '123456',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        DB::table('users')->insert([
            'name' => '演示厨友',
            'email' => 'demo@shiguang.test',
            'password' => Hash::make('12345678'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'account' => 'demo@shiguang.test',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('account');

        $this->assertGuest();
    }

    public function test_logged_in_user_can_logout(): void
    {
        DB::table('users')->insert([
            'name' => '演示厨友',
            'email' => 'demo@shiguang.test',
            'password' => Hash::make('12345678'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/login', [
            'account' => 'demo@shiguang.test',
            'password' => '12345678',
        ]);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }
}
