<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 食材购物车：加购、改数量、删除，以及提交订单
 */
class CartController extends Controller
{
    /** 购物车页：按店铺分组展示，附最近订单 */
    public function index(Request $request): View
    {
        $user = $request->user();

        $items = $user->cartItems()
            ->with(['product.supplier'])
            ->latest()
            ->get();

        $groups = $items->groupBy(fn (CartItem $item) => $item->product?->supplier_id);
        $total = round($items->sum(fn (CartItem $item) => $item->subtotal), 2);
        $count = (int) $items->sum('quantity');
        $orders = $user->orders()->take(3)->get();

        return view('account.cart', compact('items', 'groups', 'total', 'count', 'orders'));
    }

    /** 加入购物车（同一件商品重复加购会累加数量） */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:supplier_products,id'],
            'quantity' => ['nullable', 'integer', 'between:1,99'],
        ], [
            'product_id.required' => '没有指定要加的食材。',
            'product_id.exists' => '这件食材可能已经下架了。',
            'quantity.between' => '一次加购 1 到 99 份。',
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);
        $user = $request->user();

        $item = $user->cartItems()->firstOrNew([
            'supplier_product_id' => $data['product_id'],
        ]);

        $item->quantity = min(99, (int) $item->quantity + $quantity);
        $item->save();

        // 页面上的加购按钮走 fetch，这里直接回数据，不刷新页面、不跳回顶部
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'product_id' => (int) $data['product_id'],
                'quantity' => (int) $item->quantity,
                'cart_count' => $user->cartCount(),
                'message' => '已加入购物车',
            ]);
        }

        return back()->with('status', '已加入购物车，去结算时再填收货信息。');
    }

    /** 修改某一行的数量 */
    public function update(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'between:1,99'],
        ], [
            'quantity.required' => '数量不能为空。',
            'quantity.between' => '数量填 1 到 99 之间。',
        ]);

        $item->update(['quantity' => (int) $data['quantity']]);

        // 购物车页的加减号同样走 fetch，只更新数字，页面位置不动
        if ($request->expectsJson()) {
            $user = $request->user();
            $items = $user->cartItems()->with('product')->get();
            $supplierId = $item->product?->supplier_id;

            $supplierTotal = round(
                $items->filter(fn (CartItem $row) => $row->product?->supplier_id === $supplierId)
                    ->sum(fn (CartItem $row) => $row->subtotal),
                2
            );

            return response()->json([
                'ok' => true,
                'quantity' => (int) $item->quantity,
                'subtotal' => $item->subtotal,
                'supplier_total' => $supplierTotal,
                'total' => round($items->sum(fn (CartItem $row) => $row->subtotal), 2),
                'cart_count' => (int) $items->sum('quantity'),
            ]);
        }

        return redirect()->route('cart.index')->with('status', '数量已更新。');
    }

    /** 从购物车移除一行 */
    public function destroy(Request $request, CartItem $item): RedirectResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $name = $item->product?->name ?? '这件食材';
        $item->delete();

        return redirect()->route('cart.index')->with('status', "已把「{$name}」移出购物车。");
    }

    /** 清空购物车 */
    public function clear(Request $request): RedirectResponse
    {
        $request->user()->cartItems()->delete();

        return redirect()->route('cart.index')->with('status', '购物车已清空。');
    }

    /** 提交订单：把购物车存成订单快照，然后清空购物车 */
    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'min:2', 'max:30'],
            'contact_phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'address' => ['required', 'string', 'min:5', 'max:120'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'contact_name.required' => '请填写收货人。',
            'contact_name.min' => '收货人姓名至少 2 个字。',
            'contact_phone.required' => '请填写手机号，店家要联系你。',
            'contact_phone.regex' => '手机号看起来不对，填 11 位大陆手机号。',
            'address.required' => '请填写收货地址。',
            'address.min' => '地址写详细一点，至少 5 个字。',
            'note.max' => '备注最多 200 个字。',
        ]);

        $items = $user->cartItems()->with(['product.supplier'])->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', '购物车是空的，先去食材店挑点东西吧。');
        }

        $snapshot = $items->map(fn (CartItem $item) => [
            'name' => $item->product->name,
            'unit' => $item->product->unit,
            'price' => (float) $item->product->price,
            'quantity' => (int) $item->quantity,
            'subtotal' => $item->subtotal,
            'supplier' => $item->product->supplier?->name ?? '食光食材店',
        ])->values()->all();

        $total = round($items->sum(fn (CartItem $item) => $item->subtotal), 2);
        $count = (int) $items->sum('quantity');

        DB::transaction(function () use ($user, $data, $snapshot, $total, $count) {
            $user->orders()->create([
                'order_no' => $this->makeOrderNo(),
                'total' => $total,
                'item_count' => $count,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'address' => $data['address'],
                'note' => $data['note'] ?? null,
                'status' => 'pending',
                'items' => $snapshot,
            ]);

            $user->cartItems()->delete();
        });

        return redirect()->route('cart.index')
            ->with('status', '订单已提交，共 '.$count.' 件、合计 ¥'.number_format($total, 2).'，店家会尽快联系你确认。');
    }

    /** 订单号：SG + 年月日时分秒 + 三位随机数 */
    private function makeOrderNo(): string
    {
        do {
            $no = 'SG'.now()->format('ymdHis').random_int(100, 999);
        } while (Order::query()->where('order_no', $no)->exists());

        return $no;
    }
}
