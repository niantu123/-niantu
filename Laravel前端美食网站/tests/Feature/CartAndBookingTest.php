<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndBookingTest extends TestCase
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

    private function makeProduct(float $price = 12.0): SupplierProduct
    {
        $supplier = Supplier::create([
            'name' => '测试食材店'.uniqid(),
            'city' => '上海',
            'district' => '徐汇区',
            'address' => '测试路 12 号摊',
            'hours' => '05:30–19:00',
            'category' => '蔬菜',
            'rating' => 4.5,
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的食材店介绍，够十个字了。',
        ]);

        return $supplier->products()->create([
            'name' => '本地小菠菜',
            'category' => '蔬菜',
            'price' => $price,
            'unit' => '500g',
            'intro' => '当天现摘',
        ]);
    }

    private function makeRestaurant(array $extra = []): Restaurant
    {
        return Restaurant::create(array_merge([
            'name' => '测试小馆'.uniqid(),
            'city' => '上海',
            'district' => '静安区',
            'address' => '测试路 8 号',
            'phone' => '021-1234 5678',
            'hours' => '11:00–21:00',
            'avg_price' => 90,
            'rating' => 4.6,
            'cuisine' => '本帮菜',
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的餐厅介绍，凑够十个字。',
            'accepts_booking' => true,
        ], $extra));
    }

    // ---------------- 购物车 ----------------

    public function test_guest_is_redirected_from_cart(): void
    {
        $this->get('/cart')->assertRedirect('/login');
    }

    public function test_adding_same_product_twice_increments_quantity(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->post('/cart', ['product_id' => $product->id])
            ->assertRedirect();

        $this->actingAs($user)
            ->post('/cart', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'supplier_product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_cart_page_shows_items_and_total(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(12.5);

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)
            ->get('/cart')
            ->assertOk()
            ->assertSee('本地小菠菜')
            ->assertSee('25.00');
    }

    public function test_cart_quantity_can_be_updated(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id]);
        $item = $user->cartItems()->first();

        $this->actingAs($user)
            ->patch("/cart/{$item->id}", ['quantity' => 5])
            ->assertRedirect('/cart');

        $this->assertSame(5, $item->fresh()->quantity);
    }

    public function test_add_to_cart_returns_json_for_ajax_calls(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->postJson('/cart', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'product_id' => $product->id,
                'quantity' => 1,
                'cart_count' => 1,
            ]);

        // 再加一次，只回最新份数，不重定向
        $this->actingAs($user)
            ->postJson('/cart', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['quantity' => 2, 'cart_count' => 2]);
    }

    public function test_quantity_update_returns_json_totals_for_ajax_calls(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(10.0);

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id]);
        $item = $user->cartItems()->first();

        $this->actingAs($user)
            ->patchJson("/cart/{$item->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'quantity' => 3,
                'subtotal' => 30.0,
                'supplier_total' => 30.0,
                'total' => 30.0,
                'cart_count' => 3,
            ]);
    }

    public function test_ajax_add_to_cart_still_validates_product(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->postJson('/cart', ['product_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_user_cannot_touch_another_users_cart_item(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($owner)->post('/cart', ['product_id' => $product->id]);
        $item = $owner->cartItems()->first();

        $this->actingAs($other)
            ->patch("/cart/{$item->id}", ['quantity' => 9])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete("/cart/{$item->id}")
            ->assertForbidden();
    }

    public function test_checkout_creates_order_and_clears_cart(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct(10.0);

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)
            ->post('/cart/checkout', [
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'address' => '静安区测试路 8 号 302 室',
                'note' => '晚上六点后送到',
            ])
            ->assertRedirect('/cart')
            ->assertSessionHas('status');

        $order = Order::query()->firstOrFail();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(20.0, $order->total);
        $this->assertSame(2, $order->item_count);
        $this->assertSame('本地小菠菜', $order->items[0]['name']);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_requires_valid_phone(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id]);

        $this->actingAs($user)
            ->post('/cart/checkout', [
                'contact_name' => '张三',
                'contact_phone' => '123',
                'address' => '静安区测试路 8 号 302 室',
            ])
            ->assertSessionHasErrors('contact_phone');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_empty_cart_cannot_be_checked_out(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/cart/checkout', [
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'address' => '静安区测试路 8 号 302 室',
            ])
            ->assertRedirect('/cart')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    // ---------------- 订单编辑 ----------------

    /** 走一遍加购 + 结算，拿到订单 */
    private function placeOrder(User $user, SupplierProduct $product, int $quantity = 2): Order
    {
        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => $quantity]);

        $this->actingAs($user)->post('/cart/checkout', [
            'contact_name' => '张三',
            'contact_phone' => '13800138000',
            'address' => '静安区测试路 8 号 302 室',
            'note' => '晚上六点后送到',
        ])->assertSessionHas('status');

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_order_page_lists_orders_with_edit_form(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        $this->actingAs($user)
            ->get('/orders')
            ->assertOk()
            ->assertSee($order->order_no)
            ->assertSee('保存修改')
            ->assertSee('取消订单');
    }

    public function test_order_quantity_and_contact_can_be_edited_after_checkout(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        $this->actingAs($user)
            ->patch("/orders/{$order->id}", [
                'items' => [['quantity' => 5]],
                'contact_name' => '李四',
                'contact_phone' => '13900139000',
                'address' => '徐汇区测试路 99 号 1 楼',
                'note' => '改成五份',
            ])
            ->assertRedirect('/orders')
            ->assertSessionHas('status');

        $fresh = $order->fresh();

        $this->assertSame(5, $fresh->item_count);
        $this->assertSame(50.0, $fresh->total);
        $this->assertSame(5, $fresh->items[0]['quantity']);
        $this->assertSame('李四', $fresh->contact_name);
        $this->assertSame('徐汇区测试路 99 号 1 楼', $fresh->address);
    }

    public function test_order_item_can_be_removed_by_setting_quantity_to_zero(): void
    {
        $user = $this->makeUser();
        $first = $this->makeProduct(10.0);
        $second = $this->makeProduct(6.0);

        $this->actingAs($user)->post('/cart', ['product_id' => $first->id, 'quantity' => 2]);
        $this->actingAs($user)->post('/cart', ['product_id' => $second->id, 'quantity' => 1]);
        $this->actingAs($user)->post('/cart/checkout', [
            'contact_name' => '张三',
            'contact_phone' => '13800138000',
            'address' => '静安区测试路 8 号 302 室',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->patch("/orders/{$order->id}", [
                'items' => [['quantity' => 2], ['quantity' => 0]],
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'address' => '静安区测试路 8 号 302 室',
            ])
            ->assertRedirect('/orders');

        $fresh = $order->fresh();

        $this->assertCount(1, $fresh->items);
        $this->assertSame(2, $fresh->item_count);
        $this->assertSame(20.0, $fresh->total);
    }

    public function test_order_cannot_be_emptied_completely(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        $this->actingAs($user)
            ->patch("/orders/{$order->id}", [
                'items' => [['quantity' => 0]],
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'address' => '静安区测试路 8 号 302 室',
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(2, $order->fresh()->item_count);
    }

    public function test_cancelled_order_cannot_be_edited_again(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        $this->actingAs($user)
            ->delete("/orders/{$order->id}")
            ->assertRedirect('/orders');

        $this->assertSame('cancelled', $order->fresh()->status);

        $this->actingAs($user)
            ->patch("/orders/{$order->id}", [
                'items' => [['quantity' => 1]],
                'contact_name' => '张三',
                'contact_phone' => '13800138000',
                'address' => '静安区测试路 8 号 302 室',
            ])
            ->assertRedirect('/orders')
            ->assertSessionHas('error');
    }

    public function test_user_cannot_edit_another_users_order(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $order = $this->placeOrder($owner, $this->makeProduct(10.0));

        $this->actingAs($other)
            ->patch("/orders/{$order->id}", [
                'items' => [['quantity' => 1]],
                'contact_name' => '王五',
                'contact_phone' => '13700137000',
                'address' => '静安区测试路 8 号 302 室',
            ])
            ->assertForbidden();

        $this->actingAs($other)->delete("/orders/{$order->id}")->assertForbidden();
        $this->actingAs($other)->delete("/orders/{$order->id}/destroy")->assertForbidden();
    }

    public function test_cancelled_order_can_be_deleted_for_good(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        // 先取消
        $this->actingAs($user)->delete("/orders/{$order->id}")->assertRedirect('/orders');
        $this->assertSame('cancelled', $order->fresh()->status);

        // 再删除
        $this->actingAs($user)
            ->delete("/orders/{$order->id}/destroy")
            ->assertRedirect('/orders')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_pending_order_cannot_be_deleted(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        $this->actingAs($user)
            ->delete("/orders/{$order->id}/destroy")
            ->assertRedirect('/orders')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_cancelled_order_page_shows_delete_button_only_when_cancelled(): void
    {
        $user = $this->makeUser();
        $order = $this->placeOrder($user, $this->makeProduct(10.0));

        $this->actingAs($user)
            ->get('/orders')
            ->assertOk()
            ->assertDontSee('删除订单');

        $this->actingAs($user)->delete("/orders/{$order->id}");

        $this->actingAs($user)
            ->get('/orders')
            ->assertOk()
            ->assertSee('删除订单');
    }

    // ---------------- 预约 ----------------

    public function test_bookable_restaurant_accepts_booking(): void
    {
        $user = $this->makeUser();
        $restaurant = $this->makeRestaurant();

        $this->actingAs($user)
            ->post("/restaurants/{$restaurant->id}/bookings", [
                'book_date' => now()->addDay()->toDateString(),
                'book_time' => '18:30',
                'party_size' => 4,
                'contact_name' => '李四',
                'contact_phone' => '13900139000',
                'note' => '要靠窗',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'book_time' => '18:30',
            'party_size' => 4,
            'status' => Booking::STATUS_PENDING,
        ]);
    }

    public function test_restaurant_without_booking_switch_rejects_booking(): void
    {
        $user = $this->makeUser();
        $restaurant = $this->makeRestaurant(['accepts_booking' => false]);

        $this->actingAs($user)
            ->post("/restaurants/{$restaurant->id}/bookings", [
                'book_date' => now()->addDay()->toDateString(),
                'book_time' => '18:30',
                'party_size' => 2,
                'contact_name' => '李四',
                'contact_phone' => '13900139000',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_date_cannot_be_in_the_past(): void
    {
        $user = $this->makeUser();
        $restaurant = $this->makeRestaurant();

        $this->actingAs($user)
            ->post("/restaurants/{$restaurant->id}/bookings", [
                'book_date' => now()->subDay()->toDateString(),
                'book_time' => '18:30',
                'party_size' => 2,
                'contact_name' => '李四',
                'contact_phone' => '13900139000',
            ])
            ->assertSessionHasErrors('book_date');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_same_slot_cannot_be_booked_twice(): void
    {
        $user = $this->makeUser();
        $restaurant = $this->makeRestaurant();
        $date = now()->addDays(2)->toDateString();

        $payload = [
            'book_date' => $date,
            'book_time' => '19:00',
            'party_size' => 2,
            'contact_name' => '李四',
            'contact_phone' => '13900139000',
        ];

        $this->actingAs($user)->post("/restaurants/{$restaurant->id}/bookings", $payload)->assertSessionHas('status');

        $this->actingAs($user)
            ->post("/restaurants/{$restaurant->id}/bookings", $payload)
            ->assertSessionHasErrors('book_time');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_user_can_cancel_own_booking_only(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $restaurant = $this->makeRestaurant();

        $booking = Booking::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'book_date' => now()->addDays(3)->toDateString(),
            'book_time' => '12:00',
            'party_size' => 2,
            'contact_name' => '李四',
            'contact_phone' => '13900139000',
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->actingAs($other)->delete("/bookings/{$booking->id}")->assertForbidden();

        $this->actingAs($user)
            ->delete("/bookings/{$booking->id}")
            ->assertRedirect('/bookings');

        $this->assertSame(Booking::STATUS_CANCELLED, $booking->fresh()->status);
    }

    public function test_bookings_page_lists_upcoming_booking(): void
    {
        $user = $this->makeUser();
        $restaurant = $this->makeRestaurant();

        Booking::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'book_date' => now()->addDays(2)->toDateString(),
            'book_time' => '18:00',
            'party_size' => 3,
            'contact_name' => '王五',
            'contact_phone' => '13700137000',
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/bookings')
            ->assertOk()
            ->assertSee($restaurant->name)
            ->assertSee('待商家确认');
    }

    public function test_restaurant_admin_can_toggle_online_booking(): void
    {
        $owner = $this->makeUser(User::ROLE_RESTAURANT);
        $restaurant = $this->makeRestaurant([
            'owner_id' => $owner->id,
            'name' => '可切换小馆',
            'accepts_booking' => true,
        ]);

        $this->actingAs($owner)
            ->post('/account/restaurant', [
                'name' => $restaurant->name,
                'city' => '上海',
                'district' => '静安区',
                'address' => '测试路 8 号',
                'phone' => '021-1234 5678',
                'hours' => '11:00–21:00',
                'avg_price' => 90,
                'cuisine' => '本帮菜',
                'intro' => '测试用的餐厅介绍，凑够十个字。',
                'booking_notice' => '周末请提前一天订位。',
            ])
            ->assertRedirect();

        $fresh = $restaurant->fresh();

        $this->assertFalse($fresh->accepts_booking);
        $this->assertSame('周末请提前一天订位。', $fresh->booking_notice);
    }
}
