@extends('layouts.app')

@section('title', $supplier->name.' · 食材店 · 食光')

@section('content')
    <div class="rest-hero" style="background-image:url('{{ $supplier->cover }}');">
        <div class="container pb-5">
            <span class="sg-badge mb-3" style="background:rgba(255,255,255,.16);color:#fff;">{{ $supplier->category }}</span>
            <h1>{{ $supplier->name }}</h1>
            <div class="d-flex flex-wrap gap-3 small" style="color:rgba(255,255,255,.82);">
                <span>{{ $supplier->city }}{{ $supplier->district }}</span>
                <span>·</span>
                <span>{{ $supplier->products->count() }} 款在售</span>
                <span>·</span>
                <span>{{ $supplier->hours }}</span>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="info-strip">
            <div class="row g-4">
                <div class="col-6 col-lg-3">
                    <div class="info-item">评分<b class="text-warning">{{ number_format($supplier->rating, 1) }} / 5.0</b></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-item">主营类别<b>{{ $supplier->category }}</b></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-item">营业时间<b>{{ $supplier->hours }}</b></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-item">联系电话<b>{{ $supplier->phone ?? '—' }}</b></div>
                </div>
            </div>
        </div>

        @if ($images->isNotEmpty())
            <section class="mt-4 mb-5">
                <h2 class="mb-3" style="font-size:1.4rem;">店内实拍</h2>

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
                                <img src="{{ $img->path }}" alt="{{ $img->caption ?? $supplier->name }}">
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
                    <p class="text-secondary" style="line-height:2;">{{ $supplier->intro }}</p>
                    <div class="mt-3">
                        @foreach ($supplier->features ?? [] as $feature)
                            <span class="sg-badge">{{ $feature }}</span>
                        @endforeach
                    </div>
                </section>

                @if ($signature->isNotEmpty())
                    <section class="mb-5">
                        <h2 class="mb-1" style="font-size:1.4rem;">主推食材</h2>
                        <p class="text-muted small mb-3">老板说这几样最近最好，来货也最稳定。</p>
                        <div class="row g-3">
                            @foreach ($signature as $product)
                                @php($inCart = (int) ($cartCounts[$product->id] ?? 0))
                                <div class="col-12 col-md-6">
                                    <div class="dish-card">
                                        <div class="d-flex justify-content-between align-items-baseline gap-2">
                                            <h4>{{ $product->name }}</h4>
                                            <span class="menu-price">¥{{ rtrim(rtrim(number_format($product->price, 2, '.', ''), '0'), '.') }}</span>
                                        </div>
                                        <p>{{ $product->intro }}</p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="text-muted" style="font-size:.75rem;">
                                                规格：{{ $product->unit }}
                                                <span data-cart-note @if ($inCart === 0) hidden @endif>
                                                    · 购物车已有 <b data-cart-note-num>{{ $inCart }}</b> 份
                                                </span>
                                            </span>
                                            @auth
                                                <form method="POST" action="{{ route('cart.store') }}" data-cart-add>
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <button class="btn btn-primary btn-sm px-3" type="submit" data-cart-btn="text">
                                                        {{ $inCart > 0 ? '再加一份' : '加入购物车' }}
                                                    </button>
                                                </form>
                                            @else
                                                <a class="btn btn-outline-secondary btn-sm px-3" href="{{ route('login') }}">登录后购买</a>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mb-5">
                    <h2 class="mb-1" style="font-size:1.4rem;">在售商品</h2>
                    <p class="text-muted small mb-3">
                        共 {{ $supplier->products->count() }} 款，价格与库存以店里为准。
                        点「+」加进购物车，买好一起结算。
                        @auth
                            <a href="{{ route('cart.index') }}" style="color:var(--sg-primary);">去购物车 →</a>
                        @endauth
                    </p>

                    <div class="row g-4">
                        @foreach ($products as $category => $items)
                            <div class="col-12 col-md-6">
                                <h3 class="mb-2" style="font-size:1rem;color:var(--sg-primary);">{{ $category }}</h3>
                                @foreach ($items as $item)
                                    @php($inCart = (int) ($cartCounts[$item->id] ?? 0))
                                    <div class="menu-row">
                                        <div>
                                            <div style="font-size:.9375rem;">
                                                {{ $item->name }}
                                                @if ($item->is_signature)
                                                    <span class="sg-chip ms-1">主推</span>
                                                @endif
                                            </div>
                                            <div class="text-muted" style="font-size:.75rem;">
                                                {{ $item->unit }}{{ $item->intro ? ' · '.$item->intro : '' }}
                                            </div>
                                        </div>
                                        <div class="menu-row__dots"></div>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="menu-price">¥{{ rtrim(rtrim(number_format($item->price, 2, '.', ''), '0'), '.') }}</div>
                                            @auth
                                                <form method="POST" action="{{ route('cart.store') }}" data-cart-add>
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $item->id }}">
                                                    <button class="btn-add {{ $inCart > 0 ? 'is-in' : '' }}" type="submit"
                                                            data-cart-btn="count"
                                                            title="把「{{ $item->name }}」加入购物车"
                                                            aria-label="把「{{ $item->name }}」加入购物车">
                                                        {{ $inCart > 0 ? $inCart : '+' }}
                                                    </button>
                                                </form>
                                            @else
                                                <a class="btn-add" href="{{ route('login') }}" title="登录后可以购买"
                                                   aria-label="登录后可以购买">+</a>
                                            @endauth
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <div class="ingredient-card mb-4">
                    <h2 class="mb-3" style="font-size:1.15rem;">位置与交通</h2>

                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;">地址</div>
                        <div style="font-size:.9375rem;">{{ $supplier->city }}{{ $supplier->district }} {{ $supplier->address }}</div>
                    </div>

                    @if ($supplier->landmark)
                        <div class="mb-3">
                            <div class="text-muted" style="font-size:.75rem;">怎么找</div>
                            <div style="font-size:.9375rem;">{{ $supplier->landmark }}</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <div class="text-muted" style="font-size:.75rem;">营业时间</div>
                        <div style="font-size:.9375rem;">{{ $supplier->hours }}</div>
                    </div>

                    @if ($supplier->delivery)
                        <div class="mb-3">
                            <div class="text-muted" style="font-size:.75rem;">配送与起批</div>
                            <div style="font-size:.9375rem;">{{ $supplier->delivery }}</div>
                        </div>
                    @endif

                    @if ($supplier->phone)
                        <div class="mb-4">
                            <div class="text-muted" style="font-size:.75rem;">电话</div>
                            <div style="font-size:.9375rem;">{{ $supplier->phone }}</div>
                        </div>
                    @endif

                    <a href="https://uri.amap.com/marker?position={{ $supplier->lng }},{{ $supplier->lat }}&name={{ urlencode($supplier->name) }}&src=shiguang"
                       target="_blank" rel="noopener"
                       class="btn btn-outline-secondary btn-sm w-100">
                        在地图中查看位置
                    </a>
                </div>

                @if ($nearby->isNotEmpty())
                    <div class="ingredient-card">
                        <h2 class="mb-1" style="font-size:1.15rem;">其他食材店</h2>
                        <p class="text-muted small mb-3">没买到想要的，可以顺路看看这几家。</p>
                        @foreach ($nearby as $shop)
                            <a href="{{ route('suppliers.show', $shop) }}"
                               class="d-flex justify-content-between align-items-center py-2 text-decoration-none"
                               style="border-bottom:1px dashed #efe8dc;">
                                <span style="font-size:.9375rem;color:var(--sg-ink);">{{ $shop->name }}</span>
                                <span class="text-muted" style="font-size:.75rem;">{{ $shop->category }}</span>
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
                            还没有人评价，来说说你在这家店买到了什么吧。
                        @else
                            共 {{ $comments->count() }} 条，平均 {{ number_format((float) $commentsAvg, 1) }} 分。
                        @endif
                    </p>

                    @auth
                        <form method="POST" action="{{ route('comments.supplier.store', $supplier) }}" class="ingredient-card mb-4">
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
                                              placeholder="货新不新鲜、够不够秤、老板好不好说话，都写下来">{{ old('content') }}</textarea>
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
                            营业时间和到货情况可能变动，去之前建议先打个电话确认。
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @auth
        <a href="{{ route('cart.index') }}" class="cart-float" data-cart-float
           @if (auth()->user()->cartCount() === 0) hidden @endif>
            购物车 <b data-cart-float-num>{{ auth()->user()->cartCount() }}</b> 件 · 去结算
        </a>
    @endauth
@endsection
