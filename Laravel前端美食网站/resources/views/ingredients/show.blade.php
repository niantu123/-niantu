@extends('layouts.app')

@section('title', $name.' 能做什么 · 食材 · 食光')

@section('content')
    <section class="sg-page-head">
        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-7">
                    <a href="{{ route('ingredients.index') }}" class="small" style="color:var(--sg-primary);">← 全部食材</a>
                    <h1 class="mt-2">{{ $name }}</h1>
                    <p class="mb-0">
                        @if ($recipes->isEmpty())
                            站里还没有用到「{{ $name }}」的菜谱。
                        @else
                            {{ $totalUsed }} 道菜用到它，先挑一道做着看。
                        @endif
                    </p>
                </div>

                <div class="col-lg-5">
                    @if ($amounts->isNotEmpty())
                        <div class="text-muted small mb-2">常见用量</div>
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            @foreach ($amounts as $amount)
                                <span class="sg-badge">{{ $amount }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section pt-4">
        <div class="container">
            <div class="row g-4 g-lg-5">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
                        <div>
                            <h2 class="mb-1" style="font-size:1.4rem;">用到{{ $name }}的菜谱</h2>
                            <p class="text-muted small mb-0">
                                @if ($recipes->isEmpty())
                                    换个食材试试，或者去菜谱库按分类翻一翻。
                                @else
                                    共 {{ $recipes->count() }} 道，点开有完整食材清单和步骤。
                                @endif
                            </p>
                        </div>
                        @if ($recipes->isNotEmpty())
                            <a href="{{ route('recipes.index') }}" class="small" style="color:var(--sg-primary);">去菜谱库 →</a>
                        @endif
                    </div>

                    @if ($recipes->isEmpty())
                        <div class="sg-card empty-state">
                            <p class="mb-3">还没有收录用到「{{ $name }}」的菜谱。可以去菜谱库搜搜别的做法，或者先看看厨房里别的食材。</p>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <a href="{{ route('recipes.index') }}" class="btn btn-primary px-4">去菜谱库</a>
                                <a href="{{ route('ingredients.index') }}" class="btn btn-outline-secondary px-4">换一样食材</a>
                            </div>
                        </div>
                    @else
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4">
                            @foreach ($recipes as $recipe)
                                <x-recipe-card :recipe="$recipe" />
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="ingredient-card mb-4">
                        <h2 class="mb-1" style="font-size:1.15rem;">哪家店有卖</h2>
                        <p class="text-muted small mb-3">食材店里名字带「{{ $name }}」的现货，加进购物车一起结算。</p>

                        @forelse ($products as $product)
                            @php($inCart = (int) ($cartCounts[$product->id] ?? 0))
                            <div class="menu-row">
                                <div>
                                    <div style="font-size:.9375rem;">{{ $product->name }}</div>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        {{ $product->supplier?->name }} · {{ $product->unit }}
                                    </div>
                                </div>
                                <div class="menu-row__dots"></div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="menu-price">¥{{ rtrim(rtrim(number_format($product->price, 2, '.', ''), '0'), '.') }}</div>
                                    @auth
                                        <form method="POST" action="{{ route('cart.store') }}" data-cart-add>
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <button class="btn-add {{ $inCart > 0 ? 'is-in' : '' }}" type="submit"
                                                    data-cart-btn="count"
                                                    title="把「{{ $product->name }}」加入购物车"
                                                    aria-label="把「{{ $product->name }}」加入购物车">
                                                {{ $inCart > 0 ? $inCart : '+' }}
                                            </button>
                                        </form>
                                    @else
                                        <a class="btn-add" href="{{ route('login') }}" aria-label="登录后可以购买">+</a>
                                    @endauth
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">站内食材店暂时没有名字带「{{ $name }}」的商品，去店里看看同类现货吧。</p>
                        @endforelse

                        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm w-100 mt-3">
                            逛逛食材店
                        </a>
                    </div>

                    @if ($related->isNotEmpty())
                        <div class="ingredient-card">
                            <h2 class="mb-3" style="font-size:1.15rem;">换一样食材</h2>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($related as $other)
                                    <a class="sg-badge" href="{{ route('ingredients.show', ['name' => $other->name]) }}">
                                        {{ $other->name }} · {{ $other->recipe_count }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
