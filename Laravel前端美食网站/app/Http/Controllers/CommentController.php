<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * 菜谱留言：写入 comments 表，带菜谱 id
     */
    public function store(Request $request, Recipe $recipe): RedirectResponse
    {
        $data = $this->validated($request);

        $recipe->comments()->create([
            'user_id' => $request->user()->id,
            'nickname' => $request->user()->name,
            'rating' => $data['rating'],
            'content' => $data['content'],
        ]);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('status', '留言发布成功，谢谢你的反馈！')
            ->withFragment('comments');
    }

    /**
     * 餐厅评价：同样写入 comments 表，带餐厅 id
     */
    public function storeRestaurant(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $this->validated($request);

        $restaurant->comments()->create([
            'user_id' => $request->user()->id,
            'nickname' => $request->user()->name,
            'rating' => $data['rating'],
            'content' => $data['content'],
        ]);

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('status', '评价发布成功，谢谢你的分享！')
            ->withFragment('comments');
    }

    /**
     * 食材店评价：同样写入 comments 表，带食材店 id
     */
    public function storeSupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validated($request);

        $supplier->comments()->create([
            'user_id' => $request->user()->id,
            'nickname' => $request->user()->name,
            'rating' => $data['rating'],
            'content' => $data['content'],
        ]);

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('status', '评价发布成功，谢谢你的分享！')
            ->withFragment('comments');
    }

    /**
     * 食记评论：不打分，纯文字交流
     */
    public function storeNote(Request $request, Note $note): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:500'],
        ], [
            'content.required' => '说点什么再发布吧。',
            'content.min' => '评论至少 2 个字。',
            'content.max' => '评论最多 500 字。',
        ]);

        $note->comments()->create([
            'user_id' => $request->user()->id,
            'nickname' => $request->user()->name,
            'rating' => 5, // 食记评论不打分，库里该字段非空，给个固定值即可
            'content' => $data['content'],
        ]);

        return redirect()
            ->route('notes.show', $note)
            ->with('status', '评论发布成功。')
            ->withFragment('comments');
    }

    /**
     * 菜谱和餐厅共用的留言校验
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'content' => ['required', 'string', 'min:2', 'max:500'],
            'rating' => ['required', 'integer', 'between:1,5'],
        ], [
            'content.required' => '说点什么再发布吧。',
            'content.min' => '留言至少 2 个字。',
            'content.max' => '留言最多 500 字。',
            'rating.required' => '请打个分。',
            'rating.between' => '评分只能是 1 到 5 星。',
        ]);
    }
}
