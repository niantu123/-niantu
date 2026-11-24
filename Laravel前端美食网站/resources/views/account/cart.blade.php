@extends('layouts.app')

@section('title', '购物车 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">购物车</h1>
                        <p class="text-muted mb-0">
                            @if ($count > 0)
                                <span data-cart-summary>共 {{ $count }} 件食材，合计 ¥{{ number_format($total, 2) }}。</span>
                            @else
                                还没挑食材，去食材店里逛逛吧。
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('suppliers.index') }}" class="small" style="color:var(--sg-primary);">继续逛食材店 →</a>
                </div>

                @if ($items->isEmpty())
                    <div class="sg-card empty-state">
                        <p class="mb-3">购物车是空的。菜谱里的食材、食材店里的现货，看上哪个点「加入购物车」就到这里了。</p>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-primary px-4">去食材店</a>
                    </div>
                @else
                    @foreach ($groups as $supplierId => $group)
                        @php($supplier = $group->first()->product?->supplier)
                        <div class="sg-card mb-4">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <div>
                                    <a href="{{ $supplier ? route('suppliers.show', $supplier) : '#' }}"
                                       class="fw-semibold" style="color:var(--sg-ink);font-size:1.05rem;">
                                        {{ $supplier->name ?? '食光食材店' }}
                                    </a>
                                    @if ($supplier)
                                        <span class="text-muted ms-2" style="font-size:.75rem;">
                                            {{ $supplier->city }}{{ $supplier->district }} · {{ $supplier->delivery ?: '到店自取' }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-muted" style="font-size:.75rem;">
                                    小计 <span data-cart-group-total>¥{{ number_format($group->sum(fn ($i) => $i->subtotal), 2) }}</span>
                                </span>
                            </div>

                            @foreach ($group as $item)
                                @php($product = $item->product)
                                <div class="cart-row" data-cart-row>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-medium">{{ $product->name }}</div>
                                        <div class="text-muted" style="font-size:.75rem;">
                                            {{ $product->unit }} · 单价 ¥{{ rtrim(rtrim(number_format($product->price, 2, '.', ''), '0'), '.') }}
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('cart.update', $item) }}" class="cart-qty" data-cart-qty>
                                        @csrf
                                        @method('PATCH')
                                        <button class="cart-qty__btn" type="submit" name="quantity"
                                                value="{{ max(1, $item->quantity - 1) }}"
                                                data-cart-step="minus"
                                                @disabled($item->quantity <= 1)
                                                aria-label="减少一份">−</button>
                                        <span class="cart-qty__num" data-cart-qty-num>{{ $item->quantity }}</span>
                                        <button class="cart-qty__btn" type="submit" name="quantity"
                                                value="{{ min(99, $item->quantity + 1) }}"
                                                data-cart-step="plus"
                                                @disabled($item->quantity >= 99)
                                                aria-label="增加一份">+</button>
                                    </form>

                                    <div class="menu-price" style="min-width:5rem;text-align:right;" data-cart-subtotal>
                                        ¥{{ number_format($item->subtotal, 2) }}
                                    </div>

                                    <form method="POST" action="{{ route('cart.destroy', $item) }}"
                                          onsubmit="return confirm('把「{{ $product->name }}」移出购物车？');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="cart-remove" type="submit">移除</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="cart-total">
                        <div>
                            <div class="text-muted" style="font-size:.75rem;">合计（不含配送费，配送与起批按店铺说明）</div>
                            <div class="cart-total__num" data-cart-total>¥{{ number_format($total, 2) }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <form method="POST" action="{{ route('cart.clear') }}"
                                  onsubmit="return confirm('清空购物车里的全部食材？');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link text-muted text-decoration-none" type="submit">清空</button>
                            </form>
                            <button class="btn btn-primary px-4" type="button"
                                    data-bs-toggle="modal" data-bs-target="#checkoutModal">
                                去结算
                            </button>
                        </div>
                    </div>

                    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content" style="border:0;border-radius:1rem;">
                                <form method="POST" action="{{ route('cart.checkout') }}">
                                    @csrf
                                    <div class="modal-header border-0 pb-0 align-items-start">
                                        <div>
                                            <h5 class="modal-title" id="checkoutModalLabel">确认收货信息</h5>
                                            <div class="text-muted small mt-1" data-cart-modal-summary>共 {{ $count }} 件 · ¥{{ number_format($total, 2) }}</div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label" for="contact_name">收货人</label>
                                                <input type="text" name="contact_name" id="contact_name"
                                                       class="form-control @error('contact_name') is-invalid @enderror"
                                                       value="{{ old('contact_name', auth()->user()->name) }}"
                                                       maxlength="30" required>
                                                @error('contact_name')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-6">
                                                <label class="form-label" for="contact_phone">手机号</label>
                                                <input type="text" name="contact_phone" id="contact_phone"
                                                       class="form-control @error('contact_phone') is-invalid @enderror"
                                                       value="{{ old('contact_phone', auth()->user()->phone) }}"
                                                       maxlength="20" required>
                                                @error('contact_phone')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label" for="address">收货地址</label>
                                                <input type="text" name="address" id="address"
                                                       class="form-control @error('address') is-invalid @enderror"
                                                       value="{{ old('address') }}" maxlength="120"
                                                       placeholder="比如：静安区愚园路 68 号 3 号楼 502" required>
                                                @error('address')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label" for="note">备注（可选）</label>
                                                <input type="text" name="note" id="note" class="form-control"
                                                       value="{{ old('note') }}" maxlength="200"
                                                       placeholder="几点送到、要不要处理干净、分装配送…">
                                            </div>
                                        </div>

                                        <p class="text-muted mt-3 mb-0" style="font-size:.75rem;line-height:1.7;">
                                            提交后订单会分别发给各家食材店，店家确认库存和配送费后再发货。这一版是到店/电话确认的演示流程。
                                        </p>
                                    </div>

                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">再改改</button>
                                        <button type="submit" class="btn btn-primary px-4">提交订单</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($orders->isNotEmpty())
                    <section class="mt-5">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <h2 class="mb-0" style="font-size:1.3rem;">最近订单</h2>
                            <a href="{{ route('orders.index') }}" class="small" style="color:var(--sg-primary);">
                                查看并编辑全部订单 →
                            </a>
                        </div>

                        @foreach ($orders as $order)
                            <div class="sg-card mb-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <div class="fw-medium" style="font-size:.9375rem;">
                                            {{ $order->order_no }}
                                            <span class="sg-chip ms-1">{{ $order->status_label }}</span>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:.75rem;">
                                            {{ $order->created_at->format('Y-m-d H:i') }} · {{ $order->item_count }} 件 ·
                                            {{ $order->contact_name }} {{ $order->contact_phone }}
                                        </div>
                                    </div>
                                    <div class="menu-price">¥{{ number_format($order->total, 2) }}</div>
                                </div>

                                <div class="text-muted mt-2" style="font-size:.8125rem;line-height:1.8;">
                                    @foreach ($order->items as $row)
                                        {{ $row['supplier'] }}｜{{ $row['name'] }} ×{{ $row['quantity'] }}{{ ! $loop->last ? '；' : '' }}
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>
        </div>
    </div>

    @if ($errors->hasAny(['contact_name', 'contact_phone', 'address', 'note']))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var trigger = document.querySelector('[data-bs-target="#checkoutModal"]');
                if (trigger) {
                    trigger.click();
                }
            });
        </script>
    @endif
@endsection
