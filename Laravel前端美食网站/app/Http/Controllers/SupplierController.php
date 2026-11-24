<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * 食材店列表：可按关键词和主营类别筛选，每页 6 家
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $category = (string) $request->query('category');

        $suppliers = Supplier::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('intro', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderByDesc('rating')
            ->paginate(6)
            ->withQueryString();

        $categories = Supplier::query()->distinct()->orderBy('category')->pluck('category');

        return view('suppliers.index', compact('suppliers', 'categories', 'search', 'category'));
    }

    /**
     * 食材店详情：相册、主营商品、位置、留言
     */
    public function show(Supplier $supplier): View
    {
        $products = $supplier->products()->get()->groupBy('category');
        $signature = $supplier->signatureProducts()->get();
        $images = $supplier->images()->get();
        $comments = $supplier->comments()->with('user')->take(20)->get();
        $commentsAvg = $supplier->comments()->avg('rating');

        $nearby = Supplier::query()
            ->whereKeyNot($supplier->getKey())
            ->orderByDesc('rating')
            ->take(3)
            ->get();

        // 当前用户已经把这家店哪些商品放进购物车，按钮上直接显示份数
        $cartCounts = collect();

        if ($user = auth()->user()) {
            $cartCounts = $user->cartItems()
                ->whereIn('supplier_product_id', $supplier->products->pluck('id'))
                ->pluck('quantity', 'supplier_product_id');
        }

        return view('suppliers.show', compact(
            'supplier', 'products', 'signature', 'images', 'comments', 'commentsAvg', 'nearby', 'cartCounts'
        ));
    }
}
