@extends('layouts.app')

@section('title', $recipe->title.' · 食光')

@section('content')
    <div class="container py-4">
        <nav class="small text-muted mb-4">
            <a href="{{ route('home') }}" class="text-secondary">首页</a>
            <span class="mx-2">/</span>
            <a href="{{ route('recipes.index') }}" class="text-secondary">菜谱库</a>
            <span class="mx-2">/</span>
            <span>{{ $recipe->title }}</span>
        </nav>

        <div class="row g-5 align-items-center mb-5">
            <div class="col-lg-6">
                <div class="recipe-hero__cover">
                    <img src="{{ $recipe->cover }}" alt="{{ $recipe->title }}">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2">
                    <span class="sg-chip">{{ $recipe->category }}</span>
                    @if ($recipe->isWork())
                        <span class="sg-chip" style="background:#fdf3ec;color:var(--sg-primary);">厨友作品</span>
                    @endif
                </div>
                <h1 class="mt-3 mb-3" style="font-size:clamp(1.9rem,3.4vw,2.6rem);">{{ $recipe->title }}</h1>
                <p class="text-secondary" style="line-height:1.9;">{{ $recipe->intro }}</p>

                <ul class="meta-list my-4">
                    <li><b>{{ $recipe->prep_minutes }} 分钟</b>准备</li>
                    <li><b>{{ $recipe->cook_minutes }} 分钟</b>烹饪</li>
                    <li><b>{{ $recipe->difficulty }}</b>难度</li>
                    <li><b>{{ $recipe->servings }} 人份</b>份量</li>
                    @if ($recipe->calories)
                        <li><b>{{ $recipe->calories }} kcal</b>热量</li>
                    @endif
                </ul>

                <div class="d-flex flex-wrap align-items-center gap-3 pt-3 border-top" style="border-color:var(--sg-line)!important;">
                    <div class="avatar-circle">{{ mb_substr($recipe->author, 0, 1) }}</div>
                    <div>
                        <div class="fw-medium">
                            {{ $recipe->author }}
                            @if ($recipe->isWork())
                                <span class="role-badge" style="background:#fdf3ec;color:var(--sg-primary);">厨友投稿</span>
                            @endif
                        </div>
                        <div class="text-muted" style="font-size:.8125rem;">
                            发布于 {{ $recipe->created_at->format('Y 年 n 月 j 日') }}
                        </div>
                    </div>

                    <div class="ms-auto text-end">
                        <div class="stars">
                            @php($avg = $recipe->average_rating)
                            {{ str_repeat('★', (int) round($avg ?? 0)) }}{{ str_repeat('☆', 5 - (int) round($avg ?? 0)) }}
                        </div>
                        <div class="text-muted" style="font-size:.8125rem;">
                            {{ $avg ? $avg.' 分' : '暂无评分' }} · {{ $comments->count() }} 条留言
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    @auth
                        <form method="POST" action="{{ route('favorites.toggle', $recipe) }}">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm fav-btn {{ $isFavorited ? 'btn-primary is-on' : 'btn-outline-secondary' }}">
                                {{ $isFavorited ? '★ 已收藏' : '☆ 收藏这道菜' }}
                            </button>
                        </form>
                        <a href="{{ route('account.favorites') }}" class="small text-secondary">查看我的收藏</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">☆ 收藏这道菜</a>
                        <span class="small text-muted">登录后可以收藏</span>
                    @endauth
                </div>
            </div>
        </div>

        <div class="row g-4 g-lg-5">
            <div class="col-lg-8 order-2 order-lg-1">
                <h2 class="mb-1" style="font-size:1.4rem;">做法</h2>
                <p class="text-muted small mb-4">共 {{ $recipe->steps->count() }} 步，按顺序做就不会乱。</p>

                <div>
                    @foreach ($recipe->steps as $step)
                        <div class="step-item">
                            <div class="step-no">{{ $step->step_no }}</div>
                            <div class="step-text">{{ $step->content }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-4 order-1 order-lg-2">
                <div class="ingredient-card">
                    <h2 class="mb-1" style="font-size:1.15rem;">食材</h2>
                    <p class="text-muted small mb-3">{{ $recipe->servings }} 人份用量</p>
                    @foreach ($recipe->ingredients as $ingredient)
                        <div class="ingredient-item">
                            <span>{{ $ingredient->name }}</span>
                            <span>{{ $ingredient->amount }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <section class="mt-5 pt-4 border-top" style="border-color:var(--sg-line)!important;">
            <h2 class="mb-1" style="font-size:1.4rem;">哪里能吃到这道菜</h2>
            <p class="text-muted small mb-4">不想动手的话，这几家店的师傅做得比菜谱还稳。</p>

            @forelse ($restaurants as $restaurant)
                <a href="{{ route('restaurants.show', $restaurant) }}" class="rest-card mb-3">
                    <div class="rest-card__media">
                        <img src="{{ $restaurant->cover }}" alt="{{ $restaurant->name }}" loading="lazy">
                    </div>
                    <div class="rest-card__body">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h3 class="rest-card__name">{{ $restaurant->name }}</h3>
                                <div class="text-muted small">
                                    {{ $restaurant->cuisine }} · {{ $restaurant->district }} · 人均 ¥{{ $restaurant->avg_price }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="rest-score text-warning">{{ number_format($restaurant->rating, 1) }}</div>
                                <div class="text-muted" style="font-size:.75rem;">评分</div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="small">这道菜 <span class="menu-price">¥{{ number_format((float) $restaurant->pivot->price, 0) }}</span></span>
                            <span class="small fw-medium" style="color:var(--sg-primary);">看看这家店 →</span>
                        </div>
                    </div>
                </a>
            @empty
                <p class="text-muted small">暂时还没有收录做这道菜的餐厅。</p>
            @endforelse
        </section>

        @if ($related->isNotEmpty())
            <section class="mt-5">
                <h2 class="mb-4" style="font-size:1.4rem;">同类菜谱</h2>
                <div class="row g-4">
                    @foreach ($related as $item)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <x-recipe-card :recipe="$item" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-5 pt-4 border-top" id="comments" style="border-color:var(--sg-line)!important;">
            <h2 class="mb-1" style="font-size:1.4rem;">留言与评价</h2>
            <p class="text-muted small mb-4">做过这道菜？说说你的心得，或者哪里翻车了。</p>

            @if (session('status'))
                <div class="alert alert-success border-0 rounded-3">{{ session('status') }}</div>
            @endif

            @auth
                <form method="POST" action="{{ route('comments.store', $recipe) }}" class="ingredient-card mb-4">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="rating">给这道菜打分</label>
                        <select name="rating" id="rating" class="form-select @error('rating') is-invalid @enderror" style="max-width:12rem;">
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

                    <div class="mb-3">
                        <label class="form-label" for="content">留言</label>
                        <textarea name="content" id="content" rows="4"
                                  class="form-control @error('content') is-invalid @enderror"
                                  placeholder="比如：糖色炒到什么程度、火候怎么调、哪一步最容易失败">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">以「{{ auth()->user()->name }}」的身份发布</span>
                        <button class="btn btn-primary px-4" type="submit">发布留言</button>
                    </div>
                </form>
            @else
                <div class="ingredient-card mb-4 text-center py-4">
                    <p class="mb-3 text-secondary">登录后就能留言，说说你做的怎么样。</p>
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
                <p class="text-muted small">还没有人留言，来做第一个吧。</p>
            @endforelse
        </section>
    </div>
@endsection
