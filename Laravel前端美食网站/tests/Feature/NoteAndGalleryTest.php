<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Restaurant;
use App\Models\RestaurantImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoteAndGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = User::ROLE_USER, array $extra = []): User
    {
        return User::create(array_merge([
            'name' => '测试用户'.uniqid(),
            'email' => 'u'.uniqid().'@shiguang.test',
            'password' => bcrypt('abcd1234'),
            'role' => $role,
        ], $extra));
    }

    private function makeRestaurant(?int $ownerId = null, array $extra = []): Restaurant
    {
        return Restaurant::create(array_merge([
            'owner_id' => $ownerId,
            'name' => '测试小馆'.uniqid(),
            'city' => '上海',
            'district' => '静安区',
            'address' => '测试路 1 号',
            'hours' => '11:00–21:00',
            'avg_price' => 80,
            'rating' => 4.5,
            'cuisine' => '本帮菜',
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的餐厅介绍，够十个字了。',
        ], $extra));
    }

    public function test_guest_cannot_comment_on_note(): void
    {
        $note = Note::create([
            'user_id' => $this->makeUser()->id,
            'title' => '一篇心得',
            'content' => '内容足够长，超过十个字的要求。',
        ]);

        $this->post("/notes/{$note->id}/comments", ['content' => '游客评论'])
            ->assertRedirect('/login');

        $this->assertDatabaseMissing('comments', ['note_id' => $note->id]);
    }

    public function test_user_can_comment_on_note(): void
    {
        $user = $this->makeUser();
        $note = Note::create([
            'user_id' => $user->id,
            'title' => '一篇心得',
            'content' => '内容足够长，超过十个字的要求。',
        ]);

        $this->actingAs($user)->post("/notes/{$note->id}/comments", ['content' => '写得很实用，收藏了。'])
            ->assertRedirect(route('notes.show', $note).'#comments');

        $this->assertDatabaseHas('comments', ['note_id' => $note->id, 'content' => '写得很实用，收藏了。']);
    }

    public function test_user_can_publish_note_and_delete_it(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/account/notes', [
            'title' => '蒸蛋羹的水蛋比',
            'content' => '一个蛋配 1.5 倍温水，过筛两次，盖保鲜膜扎孔，中火十分钟，表面像布丁一样平。',
        ])->assertRedirect();

        $note = Note::where('title', '蒸蛋羹的水蛋比')->first();
        $this->assertNotNull($note);
        $response->assertRedirect(route('notes.show', $note));

        // 别人删不了
        $this->actingAs($this->makeUser())
            ->delete("/account/notes/{$note->id}")
            ->assertForbidden();

        // 自己能删
        $this->actingAs($user)->delete("/account/notes/{$note->id}")
            ->assertRedirect(route('account.notes'));

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_restaurant_images_can_be_uploaded_and_deleted_by_owner(): void
    {
        Storage::fake('uploads');

        $boss = $this->makeUser(User::ROLE_RESTAURANT);
        $this->makeRestaurant($boss->id);

        $this->actingAs($boss)->post('/account/restaurant/images', [
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.png'),
            ],
            'caption' => '二楼卡座',
        ])->assertRedirect(route('account.restaurant'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('restaurant_images', ['caption' => '二楼卡座']);
        $this->assertSame(2, RestaurantImage::count());

        // 普通用户不能传
        $this->actingAs($this->makeUser())
            ->post('/account/restaurant/images', ['images' => [UploadedFile::fake()->image('c.jpg')]])
            ->assertForbidden();

        // 不是自己店里的图不能删
        $otherImage = RestaurantImage::create([
            'restaurant_id' => $this->makeRestaurant()->id,
            'path' => '/uploads/restaurants/fake.jpg',
            'sort' => 0,
        ]);

        $this->actingAs($boss)->delete("/account/restaurant/images/{$otherImage->id}")
            ->assertForbidden();

        // 自己店的图能删
        $ownImage = RestaurantImage::where('restaurant_id', Restaurant::where('owner_id', $boss->id)->value('id'))->first();
        $this->actingAs($boss)->delete("/account/restaurant/images/{$ownImage->id}")
            ->assertRedirect(route('account.restaurant'));

        $this->assertDatabaseMissing('restaurant_images', ['id' => $ownImage->id]);
    }

    public function test_restaurants_index_paginates_six_per_page(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->makeRestaurant(null, ['name' => '分页测试店 '.($i + 1)]);
        }

        $first = $this->get('/restaurants');
        $first->assertOk();

        $this->assertCount(6, $first->viewData('restaurants')->items());
        $this->assertSame(7, $first->viewData('restaurants')->total());

        $second = $this->get('/restaurants?page=2');
        $this->assertCount(1, $second->viewData('restaurants')->items());
    }

    public function test_restaurant_show_displays_gallery_when_images_exist(): void
    {
        $restaurant = $this->makeRestaurant();

        RestaurantImage::create([
            'restaurant_id' => $restaurant->id,
            'path' => '/uploads/restaurants/a.jpg',
            'caption' => '前厅',
            'sort' => 0,
        ]);

        $this->get("/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertSee('gallery-carousel')
            ->assertSee('前厅');
    }
}
