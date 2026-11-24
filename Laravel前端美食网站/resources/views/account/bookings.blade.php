@extends('layouts.app')

@section('title', '我的预约 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">我的预约</h1>
                        <p class="text-muted mb-0">
                            @if ($upcoming->isNotEmpty())
                                有 {{ $upcoming->count() }} 桌在等，记得按时到店。
                            @else
                                还没有进行中的预约，挑家店订一桌吧。
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('restaurants.index') }}" class="small" style="color:var(--sg-primary);">去看可预约的店 →</a>
                </div>

                <h2 class="mb-3" style="font-size:1.15rem;">即将到来</h2>

                @forelse ($upcoming as $booking)
                    <div class="booking-item mb-3">
                        <div class="booking-item__date">
                            <b>{{ $booking->book_date->format('j') }}</b>
                            <span>{{ $booking->book_date->format('n') }} 月</span>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <a href="{{ route('restaurants.show', $booking->restaurant) }}"
                                   class="fw-semibold" style="color:var(--sg-ink);">
                                    {{ $booking->restaurant?->name ?? '餐厅已下线' }}
                                </a>
                                <span class="sg-chip">{{ $booking->book_time }} · {{ $booking->party_size }} 位</span>
                                <span class="booking-status booking-status--{{ $booking->status }}">{{ $booking->status_label }}</span>
                            </div>

                            <div class="text-muted mt-1" style="font-size:.8125rem;line-height:1.8;">
                                预约号 {{ $booking->number }} · {{ $booking->contact_name }} {{ $booking->contact_phone }}
                                @if ($booking->restaurant?->phone)
                                    · 店里电话 {{ $booking->restaurant->phone }}
                                @endif
                            </div>

                            @if ($booking->note)
                                <div class="text-secondary mt-1" style="font-size:.8125rem;">备注：{{ $booking->note }}</div>
                            @endif

                            <div class="text-muted mt-1" style="font-size:.75rem;">
                                {{ $booking->book_date->format('Y 年 n 月 j 日') }}（{{ ['日', '一', '二', '三', '四', '五', '六'][$booking->book_date->dayOfWeek] }}）
                                · 提交于 {{ $booking->created_at->format('m-d H:i') }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}"
                              onsubmit="return confirm('确定取消 {{ $booking->book_date->format('n 月 j 日') }} {{ $booking->book_time }} 的预约？');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-secondary btn-sm" type="submit">取消预约</button>
                        </form>
                    </div>
                @empty
                    <div class="sg-card empty-state">
                        <p class="mb-3">还没有预约。探店页里带「可预约」标记的餐厅，点「选日期预约」就能订位。</p>
                        <a href="{{ route('restaurants.index') }}" class="btn btn-primary px-4">去找餐厅</a>
                    </div>
                @endforelse

                @if ($past->isNotEmpty())
                    <section class="mt-5">
                        <h2 class="mb-3" style="font-size:1.15rem;">历史记录</h2>

                        @foreach ($past as $booking)
                            <div class="booking-item booking-item--past mb-2">
                                <div class="booking-item__date">
                                    <b>{{ $booking->book_date?->format('j') ?? '—' }}</b>
                                    <span>{{ $booking->book_date?->format('n') }} 月</span>
                                </div>

                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a href="{{ route('restaurants.show', $booking->restaurant) }}"
                                           style="color:var(--sg-ink);">
                                            {{ $booking->restaurant?->name ?? '餐厅已下线' }}
                                        </a>
                                        <span class="text-muted" style="font-size:.75rem;">
                                            {{ $booking->book_time }} · {{ $booking->party_size }} 位
                                        </span>
                                        <span class="booking-status booking-status--{{ $booking->status }}">{{ $booking->status_label }}</span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size:.75rem;">
                                        预约号 {{ $booking->number }} · {{ $booking->book_date?->format('Y-m-d') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection
