<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * 收藏 / 取消收藏（同一个按钮来回切）
     */
    public function toggle(Request $request, Recipe $recipe): RedirectResponse
    {
        $user = $request->user();
        $existing = $user->favorites()->where('recipe_id', $recipe->id)->first();

        if ($existing) {
            $existing->delete();

            return back()->with('status', '已取消收藏。');
        }

        $user->favorites()->create(['recipe_id' => $recipe->id]);

        return back()->with('status', '已收藏，去「用户中心 → 我的收藏」随时找回来。');
    }
}
