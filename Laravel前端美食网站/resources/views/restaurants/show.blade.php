@extends('layouts.app')

@section('title', $restaurant->name.' · 探店 · 食光')

@section('content')
    <div class="rest-hero" style="background-image:url('{{ $restaurant->cover }}');">
        <div class="container pb-5">
            <span class="sg-badge mb-3" style="background:rgba(255,255,255,.16);color:#fff;">{{ $restaurant->cuisine }}</span>
            @if ($restaurant->accepts_booking)
                <span class="sg-badge mb-3" style="background:rgba(255,255,255,.16);color:#fff;">可在线预约</span>
            @endif
            <h1>{{ $restaurant->name }}</h1>
            <div class="d-flex flex-wrap gap-3 small" style="color:rgba(255,255,255,.82);">
                <span>{{ $restaurant->city }}{{ $restaurant->district }}</span>
                <span>·</span>
                <span>人均 ¥{{ $restaurant->avg_price }}</span>
                <span>·</span>
                <span>{{ $restaurant->hours }}</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="info-strip">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="info-item">评分<b class="text-warning">{{ number_format($restaurant->rating, 1) }} / 5.0</b></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-item">人均消费<b>¥{{ $restaurant->avg_price }}</b></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-item">营业时间<b>{{ $restaurant->hours }}</b></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-item">联系电话<b>{{ $restaurant->phone ?? '—' }}</b></div>
                </div>
            </div>
        </div>

        @if ($images->isNotEmpty())
            <section class="mt-4 mb-5">
                <h2 class="mb-3" style="font-size:1.4rem;">店内环境</h2>

                <div id="galleryCarousel" class="carousel slide gallery-carousel" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        @foreach ($images as $i => $img)
                            <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="{{ $i }}"
                                    class="{{ $i === 0 ? 'active' : '' }}" aria-label="第 {{ $i + 1 }} 张"></button>
                        @endforeach
                    </div>

                    <div class="carousel-inner">
                        @foreach ($images as $i => $img)
                            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                                <img src="{{ $img->path }}" alt="{{ $img->caption ?? $restaurant->name }}">
                                @if ($img->caption)
                                    <div class="carousel-caption"><span>{{ $img->caption }}</span></div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if ($images->count() > 1)
                        <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">上一张</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">下一张</span>
                        </button>
                    @endif
                </div>

                @if ($images->count() > 1)
                    <div class="gallery-thumbs">
                        @foreach ($images as $i => $img)
                            <img src="{{ $img->path }}" class="{{ $i === 0 ? 'active' : '' }}"
                                 data-bs-target="#galleryCarousel" data-bs-slide-to="{{ $i }}"
                                 alt="{{ $img->caption ?? '店内图片' }}" loading="lazy">
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div class="row g-4 g-lg-5 mt-0">
            <div class="col-lg-8">
                <section class="mb-5">
                    <h2 class="mb-3" style="font-size:1.4rem;">关于这家店</h2>
                    <p class="text-secondary" style="line-height:2;">{{ $restaurant->intro }}</p>
                    <div class="mt-3">
                        @foreach ($restaurant->features ?? [] as $feature)
                            <span class="sg-badge">{{ $feature }}</span>
                        @endforeach
                    </div>
                </section>

                @if ($signatureDishes->isNotEmpty())
                    <section class="mb-5">
                        <h2 class="mb-1" style="font-size:1.4rem;">招牌菜</h2>
                        <p class="text-muted small mb-3">第一次来，从这几道开始点不会错。</p>
                        <div class="row g-3">
                            @foreach ($signatureDishes as $dish)
                                <div class="col-12 col-md-6">
                                    <div class="dish-card">
                                        <div class="d-flex justify-content-between align-items-baseline gap-2">
                                            <h4>{{ $dish->name }}</h4>
                                            <span class="menu-price">¥{{ number_format($dish->price, 0) }}</span>
                                        </div>
                                        <p>{{ $dish->intro }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mb-5">
                    <h2 class="mb-1" style="font-size:1.4rem;">菜单</h2>
                    <p class="text-muted small mb-3">共 {{ $restaurant->menuItems->count() }} 道，价格以店内为准。</p>

                    <div class="row g-4">
                        @foreach ($menu as $category => $items)
                            <div class="col-12 col-md-6">
                                <h3 class="mb-2" style="font-size:1rem;color:var(--sg-primary);">{{ $category }}</h3>
                                @foreach ($items as $item)
                                    <div class="menu-row">
                                        <div>
                                            <div style="font-size:.9375rem;">
                                                {{ $item->name }}
                                                @if ($item->is_signature)
                                                    <span class="sg-chip ms-1">招牌</span>
                                                @endif
                                            </div>
                                            @if ($item->intro)
                                                <div class="text-muted" style="font-size:.75rem;">{{ $item->intro }}</div>
                                            @endif
                                        </div>
                                        <div class="menu-row__dots"></div>
                                        <div class="menu-price">¥{{ number_format($item->price, 0) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="mb-5">
                    <h2 class="mb-1" style="font-size:1.4rem;">厨师团队</h2>
                    <p class="text-muted small mb-3">这几位决定了你吃到的东西。</p>

                    @foreach ($chefs as $chef)
                        <div class="staff-card">
                            <div class="staff-avatar">{{ mb_substr($chef->name, 0, 1) }}</div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-medium">{{ $chef->name }}</span>
                                    <span class="sg-chip">{{ $chef->title }}</span>
                                    <span class="text-muted" style="font-size:.75rem;">从业 {{ $chef->years }} 年</span>
                                </div>
                                <p class="mb-0 mt-2 text-secondary" style="font-size:.875rem;line-height:1.85;">{{ $chef->bio }}</p>
                            </div>
                        </div>
                    @endforeach
                </section>

                @if ($waiters->isNotEmpty())
                    <section class="mb-5">
                        <h2 class="mb-1" style="font-size:1.4rem;">服务人员</h2>
                        <p class="text-muted small mb-3">点单、加菜、忌口，找他们就行。</p>

                        @foreach ($waiters as $waiter)
                            <div class="staff-card">
                                <div class="staff-avatar">{{ mb_substr($waiter->name, 0, 1) }}</div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-medium">{{ $waiter->name }}</span>
                                        <span class="sg-chip">{{ $waiter->title }}</span>
                                        <span class="text-muted" style="font-size:.75rem;">{{ $waiter->years }} 年</span>
                                    </div>
                                    <p class="mb-0 mt-2 text-secondary" style="font-size:.875rem;line-height:1.85;">{{ $waiter->bio }}</p>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="booking-card mb-4">
                    <h2 class="mb-2" style="font-size:1.15rem;">预约到店</h2>

                    @if ($restaurant->accepts_booking)
                        <p class="text-muted small mb-3">
                            {{ $restaurant->booking_notice ?: '选好日期和时段，店家确认后到店即可。' }}
                        </p>

                        @auth
                            <button class="btn btn-primary w-100" type="button"
                                    data-bs-toggle="modal" data-bs-target="#bookingModal">
                                选日期预约
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary w-100">登录后预约</a>
                        @endauth

                        <div class="booking-meta mt-3">
                            <span>可约时段</span>
                            <b>午市 11:00–13:30 · 晚市 17:00–20:30</b>
                        </div>
                    @else
                        <p class="text-muted small mb-3">
                            这家店暂时不接在线预约。想吃的话直接打个电话，或者错峰过去碰碰运气。
                        </p>
                        @if ($restaurant->phone)
                            <a href="tel:{{ preg_replace('/[^0-9]/', '', $restaurant->phone) }}"
                               class="btn btn-outline-secondary w-100">{{ $restaurant->phone }}</a>
                        @endif
                    @endif
                </div>

                <div class="ingredient-card mb-4">
                    <h2 class="mb-3" style="font-size:1.15rem;">位置与交通</h2>

                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;">地址</div>
                        <div style="font-size:.9375rem;">{{ $restaurant->city }}{{ $restaurant->district }} {{ $restaurant->address }}</div>
                    </div>

                    @if ($restaurant->landmark)
                        <div class="mb-3">
                            <div class="text-muted" style="font-size:.75rem;">怎么找</div>
                            <div style="font-size:.9375rem;">{{ $restaurant->landmark }}</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;">营业时间</div>
                        <div style="font-size:.9375rem;">{{ $restaurant->hours }}</div>
                    </div>

                    @if ($restaurant->phone)
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:.75rem;">电话</div>
                            <div style="font-size:.9375rem;">{{ $restaurant->phone }}</div>
                        </div>
                    @endif

                    <a href="https://uri.amap.com/marker?position={{ $restaurant->lng }},{{ $restaurant->lat }}&name={{ urlencode($restaurant->name) }}&src=shiguang"
                       target="_blank" rel="noopener"
                       class="btn btn-outline-secondary btn-sm w-100">
                        在地图中查看位置
                    </a>
                </div>

                @if ($recipes->isNotEmpty())
                    <div class="ingredient-card">
                        <h2 class="mb-1" style="font-size:1.15rem;">这家店能吃到</h2>
                        <p class="text-muted small mb-3">点开可以看做法，也可以直接来吃。</p>
                        @foreach ($recipes as $recipe)
                            <a href="{{ route('recipes.show', $recipe) }}" class="d-flex justify-content-between align-items-center py-2 text-decoration-none" style="border-bottom:1px dashed #efe8dc;">
                                <span style="font-size:.9375rem;color:var(--sg-ink);">{{ $recipe->title }}</span>
                                <span class="menu-price" style="font-size:.9375rem;">¥{{ number_format((float) $recipe->pivot->price, 0) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <section class="mt-5 pt-4 border-top" id="comments" style="border-color:var(--sg-line)!important;">
            <div class="row g-4">
                <div class="col-lg-8">
                    <h2 class="mb-1" style="font-size:1.4rem;">厨友评价</h2>
                    <p class="text-muted small mb-4">
                        @if ($comments->isEmpty())
                            还没有人评价，来说说你的用餐感受吧。
                        @else
                            共 {{ $comments->count() }} 条，平均 {{ number_format((float) $commentsAvg, 1) }} 分。
                        @endif
                    </p>

                    @auth
                        <form method="POST" action="{{ route('comments.restaurant.store', $restaurant) }}" class="ingredient-card mb-4">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="rating">给这家店打分</label>
                                    <select name="rating" id="rating" class="form-select @error('rating') is-invalid @enderror">
                                        <option value="5" @selected(old('rating', 5) == 5)>★★★★★ 五星</option>
                                        <option value="4" @selected(old('rating') == 4)>★★★★☆ 四星</option>
                                        <option value="3" @selected(old('rating') == 3)>★★★☆☆ 三星</option>
                                        <option value="2" @selected(old('rating') == 2)>★★☆☆☆ 二星</option>
                                        <option value="1" @selected(old('rating') == 1)>★☆☆☆☆ 一星</option>
                                    </select>
                                    @error('rating')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label" for="content">你的评价</label>
                                    <textarea name="content" id="content" rows="3"
                                              class="form-control @error('content') is-invalid @enderror"
                                              placeholder="菜品口味、服务、环境、等位时间，随便聊聊">{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="text-muted small">以「{{ auth()->user()->name }}」的身份发布</span>
                                <button class="btn btn-primary px-4" type="submit">发布评价</button>
                            </div>
                        </form>
                    @else
                        <div class="ingredient-card mb-4 text-center py-4">
                            <p class="mb-3 text-secondary">登录后就能给这家店打分、写评价。</p>
                            <a href="{{ route('login') }}" class="btn btn-primary px-4">去登录</a>
                            <a href="{{ route('register') }}" class="btn btn-link text-decoration-none">还没有账号？注册</a>
                        </div>
                    @endauth

                    @forelse ($comments as $comment)
                        <div class="comment-item">
                            <div class="d-flex gap-3">
                                @if ($comment->user)
                                    <x-user-avatar :user="$comment->user" size="circle" />
                                @else
                                    <div class="avatar-circle">{{ mb_substr($comment->nickname, 0, 1) }}</div>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div>
                                            <span class="fw-medium">{{ $comment->nickname }}</span>
                                            <span class="stars ms-2">{{ str_repeat('★', $comment->rating) }}</span>
                                        </div>
                                        <span class="text-muted" style="font-size:.75rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mb-0 mt-2 text-secondary" style="line-height:1.85;">{{ $comment->content }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">还没有评价，来做第一个吧。</p>
                    @endforelse
                </div>

                <div class="col-lg-4">
                    <div class="ingredient-card">
                        <h2 class="mb-3" style="font-size:1.15rem;">关于评价</h2>
                        <p class="text-muted small mb-0" style="line-height:1.9;">
                            评价会记入这家店的档案，也会显示在你的用户中心里。
                            菜品价格和营业时间可能变动，去之前建议先打个电话确认。
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @auth
        @if ($restaurant->accepts_booking)
            <div class="modal fade" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content" style="border:0;border-radius:1rem;">
                        <form method="POST" action="{{ route('bookings.store', $restaurant) }}">
                            @csrf
                            <div class="modal-header border-0 pb-0 align-items-start">
                                <div>
                                    <h5 class="modal-title" id="bookingModalLabel">预约 · {{ $restaurant->name }}</h5>
                                    <div class="text-muted small mt-1">
                                        {{ $restaurant->city }}{{ $restaurant->district }} {{ $restaurant->address }}
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label" for="book_date">到店日期</label>
                                        <input type="date" name="book_date" id="book_date"
                                               class="form-control @error('book_date') is-invalid @enderror"
                                               min="{{ \Illuminate\Support\Carbon::today()->toDateString() }}"
                                               max="{{ \Illuminate\Support\Carbon::today()->addDays(\App\Models\Booking::DAYS_AHEAD)->toDateString() }}"
                                               value="{{ old('book_date', \Illuminate\Support\Carbon::today()->toDateString()) }}" required>
                                        @error('book_date')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label" for="book_time">时段</label>
                                        <select name="book_time" id="book_time"
                                                class="form-select @error('book_time') is-invalid @enderror">
                                            @foreach (['午市' => array_slice(\App\Models\Booking::TIME_SLOTS, 0, 6), '晚市' => array_slice(\App\Models\Booking::TIME_SLOTS, 6)] as $period => $slots)
                                                <optgroup label="{{ $period }}">
                                                    @foreach ($slots as $slot)
                                                        <option value="{{ $slot }}" @selected(old('book_time', '18:00') === $slot)>{{ $slot }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('book_time')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label" for="party_size">用餐人数</label>
                                        <select name="party_size" id="party_size"
                                                class="form-select @error('party_size') is-invalid @enderror">
                                            @for ($i = 1; $i <= \App\Models\Booking::MAX_PARTY; $i++)
                                                <option value="{{ $i }}" @selected((int) old('party_size', 2) === $i)>
                                                    {{ $i }} 位{{ $i === \App\Models\Booking::MAX_PARTY ? '（更多请电话）' : '' }}
                                                </option>
                                            @endfor
                                        </select>
                                        @error('party_size')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label" for="contact_name">联系人</label>
                                        <input type="text" name="contact_name" id="contact_name"
                                               class="form-control @error('contact_name') is-invalid @enderror"
                                               value="{{ old('contact_name', auth()->user()->name) }}"
                                               maxlength="30" required>
                                        @error('contact_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="contact_phone">手机号</label>
                                        <input type="text" name="contact_phone" id="contact_phone"
                                               class="form-control @error('contact_phone') is-invalid @enderror"
                                               value="{{ old('contact_phone', auth()->user()->phone) }}"
                                               placeholder="到店前一天店家会打给你" maxlength="20" required>
                                        @error('contact_phone')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label" for="note">备注（可选）</label>
                                        <input type="text" name="note" id="note" class="form-control"
                                               value="{{ old('note') }}" maxlength="200"
                                               placeholder="忌口、需要宝宝椅、想要靠窗座位…">
                                        @error('note')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <p class="text-muted mt-3 mb-0" style="font-size:.75rem;line-height:1.7;">
                                    提交后店家会电话确认，座位一般保留 15 分钟。行程有变可以随时去「我的预约」取消。
                                </p>
                            </div>

                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-link text-secondary text-decoration-none" data-bs-dismiss="modal">再看看</button>
                                <button type="submit" class="btn btn-primary px-4">提交预约</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    @if ($errors->hasAny(['book_date', 'book_time', 'party_size', 'contact_name', 'contact_phone', 'note']))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var trigger = document.querySelector('[data-bs-target="#bookingModal"]');
                if (trigger) {
                    trigger.click();
                }
            });
        </script>
    @endif
@endsection
