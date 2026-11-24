<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = User::ROLE_USER): User
    {
        return User::create([
            'name' => '测试用户'.uniqid(),
            'email' => 'u'.uniqid().'@shiguang.test',
            'password' => bcrypt('abcd1234'),
            'role' => $role,
        ]);
    }

    private function makeSupplier(?int $ownerId = null, array $extra = []): Supplier
    {
        return Supplier::create(array_merge([
            'owner_id' => $ownerId,
            'name' => '测试食材店'.uniqid(),
            'city' => '上海',
            'district' => '徐汇区',
            'address' => '测试路 12 号摊',
            'hours' => '05:30–19:00',
            'category' => '蔬菜',
            'rating' => 4.5,
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的食材店介绍，够十个字了。',
        ], $extra));
    }

    public function test_suppliers_index_paginates_six_per_page(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->makeSupplier();
        }

        $this->get('/suppliers')
            ->assertOk();

        $this->assertCount(6, Supplier::query()->paginate(6)->items());
    }

    public function test_supplier_show_displays_products_and_comments(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->products()->create([
            'name' => '本地小菠菜',
            'category' => '蔬菜',
            'price' => 8.5,
            'unit' => '500g',
            'is_signature' => true,
            'sort' => 1,
        ]);

        $this->get("/suppliers/{$supplier->id}")
            ->assertOk()
            ->assertSeeText('本地小菠菜')
            ->assertSeeText('主推食材');
    }

    public function test_ingredients_page_lists_ingredients_and_supplier_entry(): void
    {
        $supplier = $this->makeSupplier();
        $supplier->products()->create([
            'name' => '番茄', 'category' => '蔬菜', 'price' => 5, 'unit' => '500g', 'sort' => 1,
        ]);

        $this->get('/ingredients')
            ->assertOk()
            ->assertSeeText('常用食材')
            ->assertSeeText('查看全部');
    }

    public function test_only_supplier_and_admin_can_manage_supplier_shops(): void
    {
        $supplierRole = $this->makeUser(User::ROLE_SUPPLIER);
        $plain = $this->makeUser();
        $admin = $this->makeUser(User::ROLE_ADMIN);
        $restaurantAdmin = $this->makeUser(User::ROLE_RESTAURANT);

        $this->actingAs($supplierRole)->get('/account/supplier')->assertOk();
        $this->actingAs($admin)->get('/account/supplier')->assertOk();

        $this->actingAs($plain)->get('/account/supplier')->assertForbidden();
        $this->actingAs($restaurantAdmin)->get('/account/supplier')->assertForbidden();

        // 餐厅管理只允许餐厅管理员和管理员
        $this->actingAs($restaurantAdmin)->get('/account/restaurant')->assertOk();
        $this->actingAs($admin)->get('/account/restaurant')->assertOk();
        $this->actingAs($supplierRole)->get('/account/restaurant')->assertForbidden();
        $this->actingAs($plain)->get('/account/restaurant')->assertForbidden();
    }

    public function test_owner_can_add_and_remove_products(): void
    {
        $owner = $this->makeUser(User::ROLE_SUPPLIER);
        $other = $this->makeUser(User::ROLE_SUPPLIER);
        $supplier = $this->makeSupplier($owner->id);
        $otherShop = $this->makeSupplier($other->id);

        $this->actingAs($owner)
            ->post('/account/supplier/products', [
                'name' => '水东芥菜',
                'category' => '蔬菜',
                'price' => 6.8,
                'unit' => '500g',
            ])
            ->assertRedirect('/account/supplier')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('supplier_products', [
            'supplier_id' => $supplier->id,
            'name' => '水东芥菜',
        ]);

        // 不能动别人店的商品
        $product = SupplierProduct::create([
            'supplier_id' => $otherShop->id,
            'name' => '别人的菜',
            'category' => '蔬菜',
            'price' => 1,
            'unit' => '500g',
            'sort' => 1,
        ]);

        $this->actingAs($owner)
            ->delete("/account/supplier/products/{$product->id}")
            ->assertForbidden();

        // 自己店的可以删
        $mine = SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'name' => '自己的菜',
            'category' => '蔬菜',
            'price' => 2,
            'unit' => '500g',
            'sort' => 1,
        ]);

        $this->actingAs($owner)
            ->delete("/account/supplier/products/{$mine->id}")
            ->assertRedirect('/account/supplier');

        $this->assertDatabaseMissing('supplier_products', ['id' => $mine->id]);
    }

    public function test_guest_can_browse_but_not_comment_on_supplier(): void
    {
        $supplier = $this->makeSupplier();

        $this->get('/suppliers')->assertOk();
        $this->get("/suppliers/{$supplier->id}")->assertOk();

        $this->post("/suppliers/{$supplier->id}/comments", [
            'rating' => 5,
            'content' => '游客评价',
        ])->assertRedirect('/login');
    }

    public function test_user_can_comment_on_supplier(): void
    {
        $user = $this->makeUser();
        $supplier = $this->makeSupplier();

        $this->actingAs($user)
            ->post("/suppliers/{$supplier->id}/comments", [
                'rating' => 5,
                'content' => '菜很新鲜，老板实在。',
            ])
            ->assertRedirect("/suppliers/{$supplier->id}#comments");

        $this->assertDatabaseHas('comments', [
            'supplier_id' => $supplier->id,
            'content' => '菜很新鲜，老板实在。',
        ]);
    }
}
