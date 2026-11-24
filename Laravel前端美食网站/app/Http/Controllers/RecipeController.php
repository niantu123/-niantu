<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $category = (string) $request->query('category');

        $recipes = Recipe::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('intro', 'like', "%{$search}%")
                        // 搜「五花肉」这类食材，也能搜出用它的菜
                        ->orWhereHas('ingredients', fn ($ingredient) => $ingredient->where('name', 'like', "%{$search}%"));
                });
            })
            // 「厨友作品」不是真实分类，而是「用户上传的菜谱」
            ->when($category === Recipe::USER_CATEGORY, fn ($query) => $query->whereNotNull('user_id'))
            ->when($category !== '' && $category !== Recipe::USER_CATEGORY,
                fn ($query) => $query->where('category', $category))
            ->withAvg('comments', 'rating')
            ->withCount('comments')
            ->latest('id')
            ->paginate(9)
            ->withQueryString();

        $categories = $this->categoryOptions();

        return view('recipes.index', compact('recipes', 'categories', 'search', 'category'));
    }

    public function show(Recipe $recipe): View
    {
        $recipe->load(['ingredients', 'steps', 'user']);

        $comments = $recipe->comments()->with('user')->take(20)->get();
        $restaurants = $recipe->restaurants()->orderByDesc('rating')->get();
        $related = Recipe::where('category', $recipe->category)
            ->whereKeyNot($recipe->id)
            ->withAvg('comments', 'rating')
            ->limit(3)
            ->get();

        $isFavorited = auth()->check() && auth()->user()->hasFavorited($recipe->id);

        return view('recipes.show', compact('recipe', 'comments', 'restaurants', 'related', 'isFavorited'));
    }

    /**
     * 分类筛选项：真实菜系 + 「厨友作品」
     */
    public static function categoryOptions(): array
    {
        $categories = Recipe::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        if (Recipe::query()->whereNotNull('user_id')->exists()) {
            $categories[] = Recipe::USER_CATEGORY;
        }

        return $categories;
    }
}
