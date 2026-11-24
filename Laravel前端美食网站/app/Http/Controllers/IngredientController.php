<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IngredientController extends Controller
{
    /**
     * 食材页：先从菜谱食材里聚合出大家常用的食材，再把人带到对应的食材店
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // 出现次数最多的食材，点开就是这种食材的专属页面
        $ingredients = DB::table('recipe_ingredients')
            ->select('name', DB::raw('count(*) as recipe_count'))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->groupBy('name')
            ->orderByDesc('recipe_count')
            ->orderBy('name')
            ->limit(24)
            ->get();

        $suppliers = Supplier::query()
            ->withCount('products')
            ->orderByDesc('rating')
            ->take(3)
            ->get();

        $supplierTotal = Supplier::query()->count();

        return view('ingredients.index', compact('ingredients', 'suppliers', 'supplierTotal', 'search'));
    }

    /**
     * 单样食材：用到它的菜谱 + 常见用量 + 哪家店在卖
     */
    public function show(string $name): View
    {
        $name = trim($name);

        abort_if($name === '' || mb_strlen($name) > 30, 404);

        $recipes = Recipe::query()
            ->whereHas('ingredients', fn ($query) => $query->where('name', $name))
            ->withAvg('comments', 'rating')
            ->withCount('comments')
            ->latest('id')
            ->get();

        // 各道菜里这食材的用量写法：挑出现最多的几种，给个参考
        $amounts = DB::table('recipe_ingredients')
            ->where('name', $name)
            ->pluck('amount')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(4)
            ->keys();

        // 食材店里在售的相关商品（商品名里带这个食材）
        $products = SupplierProduct::query()
            ->with('supplier')
            ->where('name', 'like', "%{$name}%")
            ->orderByDesc('is_signature')
            ->orderBy('price')
            ->take(6)
            ->get();

        // 换一样食材看看
        $related = DB::table('recipe_ingredients')
            ->select('name', DB::raw('count(*) as recipe_count'))
            ->where('name', '!=', $name)
            ->groupBy('name')
            ->orderByDesc('recipe_count')
            ->orderBy('name')
            ->take(10)
            ->get();

        $totalUsed = DB::table('recipe_ingredients')->where('name', $name)->count();

        // 当前用户已经加购了清单里的哪些商品
        $cartCounts = collect();

        if ($user = auth()->user()) {
            $cartCounts = $user->cartItems()
                ->whereIn('supplier_product_id', $products->pluck('id'))
                ->pluck('quantity', 'supplier_product_id');
        }

        return view('ingredients.show', compact(
            'name', 'recipes', 'amounts', 'products', 'related', 'totalUsed', 'cartCounts'
        ));
    }
}
