<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Note;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\RestaurantImage;
use App\Models\Supplier;
use App\Models\SupplierImage;
use App\Models\SupplierProduct;
use App\Support\ImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * 用户中心首页：资料 + 安全 + 数据概览
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'favorites' => $user->favorites()->count(),
            'works' => $user->recipes()->count(),
            'comments' => DB::table('comments')->where('user_id', $user->id)->count(),
            'notes' => $user->notes()->count(),
        ];

        return view('account.index', compact('user', 'stats'));
    }

    /**
     * 更新资料：可改账号名、邮箱、手机号、简介、头像
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            // 账号既是展示名也是登录凭证，规则与注册保持一致
            'name' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[\p{Han}A-Za-z0-9_]+$/u',
                Rule::unique('users', 'name')->ignore($user->id),
            ],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^1[3-9]\d{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:120'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ], [
            'name.required' => '请填写账号。',
            'name.min' => '账号至少 2 个字。',
            'name.max' => '账号最多 20 个字。',
            'name.regex' => '账号只能用中文、字母、数字和下划线。',
            'name.unique' => '这个账号已被占用，换一个吧。',
            'email.required' => '请填写邮箱。',
            'email.email' => '邮箱格式不正确。',
            'email.unique' => '这个邮箱已经被别的账号用了。',
            'phone.regex' => '手机号看起来不对，填 11 位大陆手机号。',
            'phone.unique' => '这个手机号已经绑定过别的账号了。',
            'bio.max' => '简介最多 120 个字。',
            'avatar.image' => '头像需要是图片文件。',
            'avatar.mimes' => '头像只支持 jpg、png、webp 格式。',
            'avatar.max' => '头像大小不要超过 4MB。',
        ]);

        $oldName = $user->name;

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'bio' => ($data['bio'] ?? '') !== '' ? $data['bio'] : null,
            'updated_at' => now(),
        ];

        if ($request->hasFile('avatar')) {
            $update['avatar'] = ImageUpload::store($request->file('avatar'), 'avatars');
        }

        DB::table('users')->where('id', $user->id)->update($update);

        // 账号名在菜谱和留言里是冗余存的一份，改名后一并同步，避免旧名字残留在全站
        if ($oldName !== $data['name']) {
            DB::table('recipes')->where('user_id', $user->id)->update(['author' => $data['name']]);
            DB::table('comments')->where('user_id', $user->id)->update(['nickname' => $data['name']]);
        }

        $message = $oldName !== $data['name']
            ? '资料已更新，账号名从「'.$oldName.'」改为「'.$data['name'].'」，以后要用新账号登录。'
            : '资料已更新。';

        return back()->with('status', $message);
    }

    /**
     * 修改密码：要先验证当前密码
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'max:32', 'confirmed'],
        ], [
            'current_password.required' => '请先输入当前密码。',
            'password.required' => '请设置新密码。',
            'password.min' => '新密码至少 6 位。',
            'password.max' => '新密码最多 32 位。',
            'password.confirmed' => '两次输入的新密码不一致。',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => '当前密码不正确。']);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($data['password']),
            'updated_at' => now(),
        ]);

        return back()->with('status', '密码已修改，下次登录请用新密码。');
    }

    /**
     * 我的收藏
     */
    public function favorites(Request $request): View
    {
        $recipeIds = $request->user()->favorites()->pluck('recipe_id');

        $recipes = Recipe::whereIn('id', $recipeIds)
            ->withAvg('comments', 'rating')
            ->withCount('comments')
            ->latest('id')
            ->paginate(9);

        return view('account.favorites', compact('recipes'));
    }

    /**
     * 我的作品
     */
    public function works(Request $request): View
    {
        $recipes = $request->user()->recipes()
            ->withCount('comments')
            ->paginate(8);

        return view('account.works', compact('recipes'));
    }

    /**
     * 上传作品表单
     */
    public function createWork(): View
    {
        return view('account.works-create', [
            'categories' => Recipe::CATEGORIES,
            'difficulties' => Recipe::DIFFICULTIES,
        ]);
    }

    /**
     * 保存用户上传的菜谱：一条菜谱 + N 条食材 + N 条步骤
     */
    public function storeWork(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:40'],
            'category' => ['required', Rule::in(Recipe::CATEGORIES)],
            'intro' => ['required', 'string', 'min:10', 'max:200'],
            'prep_minutes' => ['nullable', 'integer', 'between:0,600'],
            'cook_minutes' => ['nullable', 'integer', 'between:0,600'],
            'difficulty' => ['required', Rule::in(Recipe::DIFFICULTIES)],
            'servings' => ['required', 'integer', 'between:1,20'],
            'ingredients' => ['required', 'string', 'max:2000'],
            'steps' => ['required', 'string', 'max:5000'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'cover_url' => ['nullable', 'url', 'max:255'],
        ], [
            'title.required' => '给这道菜起个名字吧。',
            'title.min' => '菜名至少 2 个字。',
            'title.max' => '菜名最多 40 个字。',
            'category.required' => '请选择菜系分类。',
            'category.in' => '菜系分类不在可选范围内。',
            'intro.required' => '写一句简介，让大家知道这道菜的特点。',
            'intro.min' => '简介至少 10 个字。',
            'intro.max' => '简介最多 200 个字。',
            'difficulty.in' => '难度只能是简单、中等或有点难。',
            'servings.between' => '份量请填 1 到 20 人。',
            'ingredients.required' => '食材不能为空，每行写「食材名 用量」。',
            'steps.required' => '步骤不能为空，每行写一步。',
            'cover.image' => '封面需要是图片文件。',
            'cover.mimes' => '封面只支持 jpg、png、webp 格式。',
            'cover.max' => '封面大小不要超过 4MB。',
            'cover_url.url' => '图片地址需要是完整的网址。',
        ]);

        // 封面：优先上传的文件，其次图片地址，都没有就用默认图
        $cover = ImageUpload::store($request->file('cover'), 'recipes')
            ?? (($data['cover_url'] ?? null) ?: '/images/recipes/default-cover.svg');

        $recipe = Recipe::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'cover' => $cover,
            'intro' => $data['intro'],
            'prep_minutes' => (int) ($data['prep_minutes'] ?? 0),
            'cook_minutes' => (int) ($data['cook_minutes'] ?? 0),
            'difficulty' => $data['difficulty'],
            'servings' => (int) $data['servings'],
            'author' => $request->user()->name,
        ]);

        // 食材：每行「食材名 用量」，支持空格、逗号、竖线分隔
        foreach (preg_split('/\r\n|\r|\n/', $data['ingredients']) as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $name = $line;
            $amount = '适量';

            if (preg_match('/^(.+?)[\s,，|]+(.+)$/u', $line, $matches)) {
                $name = trim($matches[1]);
                $amount = trim($matches[2]);
            }

            $recipe->ingredients()->create([
                'name' => mb_substr($name, 0, 60),
                'amount' => mb_substr($amount, 0, 40),
                'sort' => $index,
            ]);
        }

        // 步骤：每行一步
        $stepNo = 0;
        foreach (preg_split('/\r\n|\r|\n/', $data['steps']) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $stepNo++;
            $recipe->steps()->create([
                'step_no' => $stepNo,
                'content' => $line,
            ]);
        }

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('status', '作品已发布，快去看看效果吧。');
    }

    /**
     * 删除自己的作品
     */
    public function destroyWork(Request $request, Recipe $recipe): RedirectResponse
    {
        abort_unless($recipe->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $recipe->delete();

        return redirect()->route('account.works')->with('status', '作品已删除。');
    }

    /**
     * 餐厅管理：没有餐厅时是创建表单，有餐厅时是编辑 + 菜单管理
     * 管理员可以切换查看任意一家店，餐厅管理员只看自己那一家
     */
    public function restaurant(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canManageRestaurant(), 403);

        $shops = $user->isAdmin()
            ? Restaurant::query()->orderBy('name')->get()
            : collect();

        $restaurant = $user->isAdmin()
            ? ($shops->firstWhere('id', (int) $request->query('shop')) ?: $shops->first())
            : $user->restaurants()->first();

        $menu = $restaurant ? $restaurant->menuItems()->get() : collect();
        $dishes = $restaurant ? $restaurant->signatureDishes()->get() : collect();
        $comments = $restaurant ? $restaurant->comments()->take(10)->get() : collect();

        return view('account.restaurant', compact('restaurant', 'menu', 'dishes', 'comments', 'shops'));
    }

    /**
     * 当前账号正在操作的那家餐厅（管理员按 shop 参数取，其他角色取自己的店）
     */
    private function managedRestaurant(Request $request): ?Restaurant
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Restaurant::query()
                ->when($request->filled('shop'), fn ($query) => $query->whereKey((int) $request->input('shop')))
                ->orderBy('name')
                ->first();
        }

        return $user->restaurants()->first();
    }

    /**
     * 新建 / 更新自己的餐厅资料
     */
    public function saveRestaurant(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRestaurant(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'city' => ['required', 'string', 'max:30'],
            'district' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'hours' => ['required', 'string', 'max:60'],
            'avg_price' => ['required', 'integer', 'between:1,9999'],
            'cuisine' => ['required', 'string', 'max:30'],
            'intro' => ['required', 'string', 'min:10', 'max:1000'],
            'features' => ['nullable', 'string', 'max:200'],
            'accepts_booking' => ['nullable', 'boolean'],
            'booking_notice' => ['nullable', 'string', 'max:120'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:6144'],
            'cover_url' => ['nullable', 'url', 'max:255'],
        ], [
            'name.required' => '请填写餐厅名称。',
            'name.min' => '餐厅名称至少 2 个字。',
            'city.required' => '请填写城市。',
            'district.required' => '请填写区县。',
            'address.required' => '请填写详细地址。',
            'hours.required' => '请填写营业时间。',
            'avg_price.required' => '请填写人均消费。',
            'avg_price.between' => '人均消费填 1 到 9999 之间。',
            'cuisine.required' => '请填写菜系。',
            'intro.required' => '写一段餐厅介绍吧。',
            'intro.min' => '介绍至少 10 个字。',
            'cover.image' => '封面需要是图片文件。',
            'cover.max' => '封面大小不要超过 6MB。',
            'cover_url.url' => '图片地址需要是完整的网址。',
        ]);

        $restaurant = $this->managedRestaurant($request);

        $payload = [
            'name' => $data['name'],
            'city' => $data['city'],
            'district' => $data['district'],
            'address' => $data['address'],
            'landmark' => $data['landmark'] ?? null,
            'phone' => $data['phone'] ?? null,
            'hours' => $data['hours'],
            'avg_price' => (int) $data['avg_price'],
            'cuisine' => $data['cuisine'],
            'intro' => $data['intro'],
            'accepts_booking' => (bool) ($data['accepts_booking'] ?? false),
            'booking_notice' => ($data['booking_notice'] ?? null) ?: null,
            'features' => ($data['features'] ?? null) ? array_values(array_filter(array_map('trim', preg_split('/[,，、\s]+/u', $data['features'])))) : null,
        ];

        $cover = ImageUpload::store($request->file('cover'), 'restaurants')
            ?? (($data['cover_url'] ?? null) ?: null);

        if ($cover) {
            $payload['cover'] = $cover;
        }

        if ($restaurant) {
            $restaurant->update($payload);
            $message = '餐厅资料已更新。';
        } else {
            $restaurant = Restaurant::create($payload + [
                'owner_id' => $user->isAdmin() ? null : $user->id,
                'rating' => 4.5,
                'cover' => $cover ?: '/images/recipes/default-cover.svg',
            ]);
            $message = '餐厅已创建，现在可以添加菜单了。';
        }

        return redirect()
            ->route('account.restaurant', $user->isAdmin() ? ['shop' => $restaurant->id] : [])
            ->with('status', $message);
    }

    /**
     * 新增菜单项（可标记为招牌菜）
     */
    public function storeMenuItem(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRestaurant(), 403);

        $restaurant = $this->managedRestaurant($request);
        abort_if(! $restaurant, 403, '请先创建餐厅资料。');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'category' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'between:0,9999'],
            'intro' => ['nullable', 'string', 'max:140'],
            'is_signature' => ['nullable', 'boolean'],
        ], [
            'name.required' => '请填写菜名。',
            'category.required' => '请选择分类。',
            'price.required' => '请填写价格。',
            'price.between' => '价格填 0 到 9999 之间。',
        ]);

        $restaurant->menuItems()->create([
            'name' => $data['name'],
            'category' => $data['category'],
            'price' => (float) $data['price'],
            'intro' => $data['intro'] ?? null,
            'is_signature' => $request->boolean('is_signature'),
            'sort' => (int) ($restaurant->menuItems()->max('sort') + 1),
        ]);

        return redirect()
            ->route('account.restaurant', $user->isAdmin() ? ['shop' => $restaurant->id] : [])
            ->with('status', '菜单已添加。');
    }

    /**
     * 删除菜单项（只能删自己店的，管理员可以删任意店）
     */
    public function destroyMenuItem(Request $request, MenuItem $item): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRestaurant(), 403);
        abort_unless($item->restaurant && ($item->restaurant->owner_id === $user->id || $user->isAdmin()), 403);

        $item->delete();

        return redirect()
            ->route('account.restaurant', $user->isAdmin() ? ['shop' => $item->restaurant_id] : [])
            ->with('status', '菜单项已删除。');
    }

    /**
     * 我的食记
     */
    public function notes(Request $request): View
    {
        $notes = $request->user()->notes()
            ->withCount('comments')
            ->paginate(8);

        return view('account.notes', compact('notes'));
    }

    /**
     * 写食记表单
     */
    public function createNote(): View
    {
        return view('account.notes-create');
    }

    /**
     * 保存食记
     */
    public function storeNote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:60'],
            'content' => ['required', 'string', 'min:10', 'max:5000'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ], [
            'title.required' => '给这篇食记起个标题。',
            'title.min' => '标题至少 2 个字。',
            'title.max' => '标题最多 60 个字。',
            'content.required' => '写写你的做菜过程和体会吧。',
            'content.min' => '内容至少 10 个字。',
            'content.max' => '内容最多 5000 字。',
            'cover.image' => '封面需要是图片文件。',
            'cover.mimes' => '封面只支持 jpg、png、webp 格式。',
            'cover.max' => '封面大小不要超过 4MB。',
        ]);

        $note = Note::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'cover' => ImageUpload::store($request->file('cover'), 'notes'),
        ]);

        return redirect()
            ->route('notes.show', $note)
            ->with('status', '食记已发布，去看看大家的反应吧。');
    }

    /**
     * 删除自己的食记
     */
    public function destroyNote(Request $request, Note $note): RedirectResponse
    {
        abort_unless($note->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $note->delete();

        return redirect()->route('account.notes')->with('status', '食记已删除。');
    }

    /**
     * 上传店内图片（可一次多选，最多 6 张），存入餐厅相册
     */
    public function storeRestaurantImages(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRestaurant(), 403);

        $restaurant = $this->managedRestaurant($request);
        abort_if(! $restaurant, 403, '请先创建餐厅资料。');

        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:6'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:40'],
        ], [
            'images.required' => '请选择要上传的图片。',
            'images.max' => '一次最多上传 6 张。',
            'images.*.image' => '上传的文件需要是图片。',
            'images.*.mimes' => '图片只支持 jpg、png、webp 格式。',
            'images.*.max' => '每张图片不要超过 4MB。',
            'caption.max' => '图片说明最多 40 个字。',
        ]);

        $sort = (int) $restaurant->images()->max('sort');
        $count = 0;

        foreach ($request->file('images') as $file) {
            $path = ImageUpload::store($file, 'restaurants');

            if ($path) {
                $restaurant->images()->create([
                    'path' => $path,
                    'caption' => ($data['caption'] ?? null) ?: null,
                    'sort' => ++$sort,
                ]);
                $count++;
            }
        }

        return redirect()
            ->route('account.restaurant', $user->isAdmin() ? ['shop' => $restaurant->id] : [])
            ->with('status', "已上传 {$count} 张店内图片，餐厅页的相册会同步更新。");
    }

    /**
     * 删除店内图片（只能删自己店的，管理员可以删任意店）
     */
    public function destroyRestaurantImage(Request $request, RestaurantImage $image): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageRestaurant(), 403);
        abort_unless($image->restaurant && ($image->restaurant->owner_id === $user->id || $user->isAdmin()), 403);

        $shopId = $image->restaurant_id;
        $image->delete();

        return redirect()
            ->route('account.restaurant', $user->isAdmin() ? ['shop' => $shopId] : [])
            ->with('status', '图片已删除。');
    }

    /**
     * 食材店管理：没有店铺时是创建表单，有店铺时是编辑 + 商品 + 相册
     * 管理员可以切换查看任意一家店，食材供应商只看自己那一家
     */
    public function supplier(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canManageSupplier(), 403);

        $shops = $user->isAdmin()
            ? Supplier::query()->orderBy('name')->get()
            : collect();

        $supplier = $user->isAdmin()
            ? ($shops->firstWhere('id', (int) $request->query('shop')) ?: $shops->first())
            : $user->suppliers()->first();

        $products = $supplier ? $supplier->products()->get() : collect();
        $signature = $supplier ? $supplier->signatureProducts()->get() : collect();
        $comments = $supplier ? $supplier->comments()->take(10)->get() : collect();

        return view('account.supplier', compact('supplier', 'products', 'signature', 'comments', 'shops'));
    }

    /**
     * 当前账号正在操作的那家食材店（管理员按 shop 参数取，供应商取自己的店）
     */
    private function managedSupplier(Request $request): ?Supplier
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return Supplier::query()
                ->when($request->filled('shop'), fn ($query) => $query->whereKey((int) $request->input('shop')))
                ->orderBy('name')
                ->first();
        }

        return $user->suppliers()->first();
    }

    /**
     * 新建 / 更新自己的食材店资料
     */
    public function saveSupplier(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSupplier(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'city' => ['required', 'string', 'max:30'],
            'district' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'hours' => ['required', 'string', 'max:60'],
            'category' => ['required', 'string', 'max:30'],
            'delivery' => ['nullable', 'string', 'max:60'],
            'intro' => ['required', 'string', 'min:10', 'max:1000'],
            'features' => ['nullable', 'string', 'max:200'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:6144'],
            'cover_url' => ['nullable', 'url', 'max:255'],
        ], [
            'name.required' => '请填写店铺名称。',
            'name.min' => '店铺名称至少 2 个字。',
            'city.required' => '请填写城市。',
            'district.required' => '请填写区县。',
            'address.required' => '请填写详细地址。',
            'hours.required' => '请填写营业时间。',
            'category.required' => '请填写主营类别。',
            'intro.required' => '写一段店铺介绍吧。',
            'intro.min' => '介绍至少 10 个字。',
            'cover.image' => '封面需要是图片文件。',
            'cover.max' => '封面大小不要超过 6MB。',
            'cover_url.url' => '图片地址需要是完整的网址。',
        ]);

        $supplier = $this->managedSupplier($request);

        $payload = [
            'name' => $data['name'],
            'city' => $data['city'],
            'district' => $data['district'],
            'address' => $data['address'],
            'landmark' => $data['landmark'] ?? null,
            'phone' => $data['phone'] ?? null,
            'hours' => $data['hours'],
            'category' => $data['category'],
            'delivery' => $data['delivery'] ?? null,
            'intro' => $data['intro'],
            'features' => ($data['features'] ?? null) ? array_values(array_filter(array_map('trim', preg_split('/[,，、\s]+/u', $data['features'])))) : null,
        ];

        $cover = ImageUpload::store($request->file('cover'), 'suppliers')
            ?? (($data['cover_url'] ?? null) ?: null);

        if ($cover) {
            $payload['cover'] = $cover;
        }

        if ($supplier) {
            $supplier->update($payload);
            $message = '食材店资料已更新。';
        } else {
            $supplier = Supplier::create($payload + [
                'owner_id' => $user->isAdmin() ? null : $user->id,
                'rating' => 4.5,
                'cover' => $cover ?: '/images/recipes/default-cover.svg',
            ]);
            $message = '食材店已创建，现在可以上架商品了。';
        }

        return redirect()
            ->route('account.supplier', $user->isAdmin() ? ['shop' => $supplier->id] : [])
            ->with('status', $message);
    }

    /**
     * 上架一条商品（可标记为主推）
     */
    public function storeSupplierProduct(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSupplier(), 403);

        $supplier = $this->managedSupplier($request);
        abort_if(! $supplier, 403, '请先创建食材店资料。');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'category' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'between:0,99999'],
            'unit' => ['required', 'string', 'max:20'],
            'intro' => ['nullable', 'string', 'max:140'],
            'is_signature' => ['nullable', 'boolean'],
        ], [
            'name.required' => '请填写商品名称。',
            'category.required' => '请选择分类。',
            'price.required' => '请填写价格。',
            'price.between' => '价格填 0 到 99999 之间。',
            'unit.required' => '请填写规格，比如 500g / 一箱 10 斤。',
        ]);

        $supplier->products()->create([
            'name' => $data['name'],
            'category' => $data['category'],
            'price' => (float) $data['price'],
            'unit' => $data['unit'],
            'intro' => $data['intro'] ?? null,
            'is_signature' => $request->boolean('is_signature'),
            'sort' => (int) ($supplier->products()->max('sort') + 1),
        ]);

        return redirect()
            ->route('account.supplier', $user->isAdmin() ? ['shop' => $supplier->id] : [])
            ->with('status', '商品已上架。');
    }

    /**
     * 下架商品（只能删自己店的，管理员可以删任意店）
     */
    public function destroySupplierProduct(Request $request, SupplierProduct $product): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSupplier(), 403);
        abort_unless($product->supplier && ($product->supplier->owner_id === $user->id || $user->isAdmin()), 403);

        $shopId = $product->supplier_id;
        $product->delete();

        return redirect()
            ->route('account.supplier', $user->isAdmin() ? ['shop' => $shopId] : [])
            ->with('status', '商品已下架。');
    }

    /**
     * 上传店内图片（一次最多 6 张）
     */
    public function storeSupplierImages(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSupplier(), 403);

        $supplier = $this->managedSupplier($request);
        abort_if(! $supplier, 403, '请先创建食材店资料。');

        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:6'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:40'],
        ], [
            'images.required' => '请选择要上传的图片。',
            'images.max' => '一次最多上传 6 张。',
            'images.*.image' => '上传的文件需要是图片。',
            'images.*.mimes' => '图片只支持 jpg、png、webp 格式。',
            'images.*.max' => '每张图片不要超过 4MB。',
            'caption.max' => '图片说明最多 40 个字。',
        ]);

        $sort = (int) $supplier->images()->max('sort');
        $count = 0;

        foreach ($request->file('images') as $file) {
            $path = ImageUpload::store($file, 'suppliers');

            if ($path) {
                $supplier->images()->create([
                    'path' => $path,
                    'caption' => ($data['caption'] ?? null) ?: null,
                    'sort' => ++$sort,
                ]);
                $count++;
            }
        }

        return redirect()
            ->route('account.supplier', $user->isAdmin() ? ['shop' => $supplier->id] : [])
            ->with('status', "已上传 {$count} 张店内图片，店铺页的相册会同步更新。");
    }

    /**
     * 删除店内图片
     */
    public function destroySupplierImage(Request $request, SupplierImage $image): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canManageSupplier(), 403);
        abort_unless($image->supplier && ($image->supplier->owner_id === $user->id || $user->isAdmin()), 403);

        $shopId = $image->supplier_id;
        $image->delete();

        return redirect()
            ->route('account.supplier', $user->isAdmin() ? ['shop' => $shopId] : [])
            ->with('status', '图片已删除。');
    }
}
