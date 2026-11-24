@extends('layouts.app')

@section('title', '我的订单 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">我的订单</h1>
                        <p class="text-muted mb-0">
                            下单后还能改：数量、收货信息都可以再调整；不想买了先取消，取消后的订单还能删掉。
                        </p>
                    </div>
                    <a href="{{ route('suppliers.index') }}" class="small" style="color:var(--sg-primary);">继续逛食材店 →</a>
                </div>

                @if ($orders->isEmpty())
                    <div class="sg-card empty-state">
                        <p class="mb-3">还没有订单。食材店里挑好东西、结算之后就出现在这里。</p>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-primary px-4">去食材店</a>
                    </div>
                @else
                    @foreach ($orders as $order)
                        @php($editable = $order->status === 'pending')

                        <div class="sg-card mb-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="fw-semibold" style="font-size:.9375rem;">
                                        {{ $order->order_no }}
                                        <span class="booking-status booking-status--{{ $order->status }} ms-1">{{ $order->status_label }}</span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size:.75rem;">
                                        {{ $order->created_at->format('Y-m-d H:i') }} 下单 · 共 {{ $order->item_count }} 件
                                    </div>
                                </div>
                                <div class="menu-price">¥{{ number_format($order->total, 2) }}</div>
                            </div>

                            @if ($editable)
                                <form method="POST" action="{{ route('orders.update', $order) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')

                                    <div class="order-edit">
                                        @foreach ($order->items as $index => $row)
                                            <div class="order-edit__row">
                                                <div class="flex-grow-1 min-w-0">
                                                    <div style="font-size:.9375rem;">{{ $row['name'] }}</div>
                                                    <div class="text-muted" style="font-size:.75rem;">
                                                        {{ $row['supplier'] }} · {{ $row['unit'] }} · 单价 ¥{{ number_format((float) $row['price'], 2) }}
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center gap-2">
                                                    <label class="text-muted mb-0" style="font-size:.75rem;"
                                                           for="qty-{{ $order->id }}-{{ $index }}">数量</label>
                                                    <input type="number" min="0" max="99"
                                                           name="items[{{ $index }}][quantity]"
                                                           id="qty-{{ $order->id }}-{{ $index }}"
                                                           class="form-control form-control-sm order-edit__qty"
                                                           value="{{ $row['quantity'] }}">
                                                    <span class="menu-price" style="font-size:.9375rem;min-width:4.5rem;text-align:right;">
                                                        ¥{{ number_format((float) $row['price'] * $row['quantity'], 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <p class="text-muted mt-2 mb-3" style="font-size:.75rem;">
                                        数量填 0 就把这件从订单里去掉，保存后金额自动重算。
                                    </p>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label" for="name-{{ $order->id }}">收货人</label>
                                            <input type="text" name="contact_name" id="name-{{ $order->id }}"
                                                   class="form-control form-control-sm @error('contact_name') is-invalid @enderror"
                                                   value="{{ old('contact_name', $order->contact_name) }}" maxlength="30" required>
                                            @error('contact_name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label" for="phone-{{ $order->id }}">手机号</label>
                                            <input type="text" name="contact_phone" id="phone-{{ $order->id }}"
                                                   class="form-control form-control-sm @error('contact_phone') is-invalid @enderror"
                                                   value="{{ old('contact_phone', $order->contact_phone) }}" maxlength="20" required>
                                            @error('contact_phone')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label" for="note-{{ $order->id }}">备注</label>
                                            <input type="text" name="note" id="note-{{ $order->id }}" class="form-control form-control-sm"
                                                   value="{{ old('note', $order->note) }}" maxlength="200"
                                                   placeholder="送到时间、处理方式…">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="address-{{ $order->id }}">收货地址</label>
                                            <input type="text" name="address" id="address-{{ $order->id }}"
                                                   class="form-control form-control-sm @error('address') is-invalid @enderror"
                                                   value="{{ old('address', $order->address) }}" maxlength="120" required>
                                            @error('address')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    @error('items')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror

                                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                        <span class="text-muted" style="font-size:.75rem;">保存后商家按最新内容备货</span>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-secondary btn-sm" type="submit"
                                                    form="cancel-{{ $order->id }}"
                                                    onclick="return confirm('确定取消这单吗？商家就不会备货了。');">
                                                取消订单
                                            </button>
                                            <button class="btn btn-primary px-4" type="submit">保存修改</button>
                                        </div>
                                    </div>
                                </form>

                                <form id="cancel-{{ $order->id }}" method="POST"
                                      action="{{ route('orders.cancel', $order) }}" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @else
                                <div class="mt-3">
                                    @foreach ($order->items as $row)
                                        <div class="d-flex justify-content-between py-2" style="border-bottom:1px dashed #efe8dc;font-size:.875rem;">
                                            <span class="text-secondary">
                                                {{ $row['supplier'] }}｜{{ $row['name'] }} ×{{ $row['quantity'] }}
                                            </span>
                                            <span class="text-muted">¥{{ number_format((float) $row['price'] * $row['quantity'], 2) }}</span>
                                        </div>
                                    @endforeach
                                    <div class="text-muted mt-2" style="font-size:.75rem;">
                                        收货：{{ $order->contact_name }} {{ $order->contact_phone }} · {{ $order->address }}
                                    </div>

                                    @if ($order->status === 'cancelled')
                                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                            <span class="text-muted" style="font-size:.75rem;">
                                                已取消的订单可以删掉，删除后不再出现在这个列表里。
                                            </span>
                                            <form method="POST" action="{{ route('orders.destroy', $order) }}"
                                                  onsubmit="return confirm('删除订单 {{ $order->order_no }}？删除后记录就找不回来了。');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="page" value="{{ $orders->currentPage() }}">
                                                <button class="btn btn-outline-secondary btn-sm" type="submit">删除订单</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="mt-4 d-flex justify-content-center">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
