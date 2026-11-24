<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 食材订单：提交后仍然可以改数量、改收货信息，或者取消
 */
class OrderController extends Controller
{
    /** 我的订单列表 */
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->paginate(5);

        return view('account.orders', compact('orders'));
    }

    /** 修改待发货订单：数量（填 0 表示删掉这一行）+ 收货信息 */
    public function update(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== 'pending') {
            return redirect()->route('orders.index')
                ->with('error', '这单已经不能改了，状态是「'.$order->status_label.'」。');
        }

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.quantity' => ['required', 'integer', 'between:0,99'],
            'contact_name' => ['required', 'string', 'min:2', 'max:30'],
            'contact_phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'address' => ['required', 'string', 'min:5', 'max:120'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'items.required' => '订单里没有商品了。',
            'items.*.quantity.required' => '数量不能空着。',
            'items.*.quantity.between' => '数量填 0 到 99，填 0 就是不要这件。',
            'contact_name.required' => '请填写收货人。',
            'contact_name.min' => '收货人姓名至少 2 个字。',
            'contact_phone.required' => '请填写手机号。',
            'contact_phone.regex' => '手机号看起来不对，填 11 位大陆手机号。',
            'address.required' => '请填写收货地址。',
            'address.min' => '地址写详细一点，至少 5 个字。',
            'note.max' => '备注最多 200 个字。',
        ]);

        $items = collect($order->items ?? [])
            ->values()
            ->map(function (array $row, int $index) use ($data) {
                $quantity = (int) ($data['items'][$index]['quantity'] ?? 0);
                $row['quantity'] = $quantity;
                $row['subtotal'] = round((float) $row['price'] * $quantity, 2);

                return $row;
            })
            ->filter(fn (array $row) => $row['quantity'] > 0)
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => '至少要留一件商品；全都不想要的话，点下面的「取消订单」。',
            ]);
        }

        $total = round($items->sum(fn (array $row) => (float) $row['price'] * $row['quantity']), 2);
        $count = (int) $items->sum('quantity');

        $order->update([
            'items' => $items->all(),
            'total' => $total,
            'item_count' => $count,
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'address' => $data['address'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('orders.index')
            ->with('status', '订单 '.$order->order_no.' 已更新：'.$count.' 件，合计 ¥'.number_format($total, 2).'。');
    }

    /**
     * 删除订单：只有已取消的订单可以删掉，从列表里彻底移除
     */
    public function destroy(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== 'cancelled') {
            return redirect()->route('orders.index')
                ->with('error', '只有已取消的订单能删除，这单状态是「'.$order->status_label.'」。');
        }

        $orderNo = $order->order_no;
        $order->delete();

        // 删完可能正好空了最后一页，算一下应该回到第几页
        $perPage = 5;
        $total = $request->user()->orders()->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) $request->input('page', 1)), $lastPage);

        return redirect()->route('orders.index', $page > 1 ? ['page' => $page] : [])
            ->with('status', '订单 '.$orderNo.' 已删除。');
    }

    /** 取消订单 */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->status !== 'pending') {
            return redirect()->route('orders.index')
                ->with('error', '这单已经不能取消了，状态是「'.$order->status_label.'」。');
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->route('orders.index')
            ->with('status', '订单 '.$order->order_no.' 已取消，商家不会再备货。');
    }
}
