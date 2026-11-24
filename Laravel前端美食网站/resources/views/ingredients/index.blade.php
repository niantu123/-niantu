@extends('layouts.app')

@section('title', '食材 · 食光')

@section('content')
    <section class="sg-page-head">
        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-6">
                    <h1>食材</h1>
                    <p>从一样食材出发，看看能做出什么；也可以直接找到卖它的食材店。</p>
                </div>
                <div class="col-lg-6">
                    <form class="sg-search ms-lg-auto" action="{{ route('ingredients.index') }}" method="GET">
                        <input type="search" name="q" value="{{ $search }}" placeholder="搜食材，比如「番茄」「豆腐」" aria-label="搜索食材">
                        <button class="btn btn-primary px-4" type="submit">搜索</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="mb-1" style="font-size:1.4rem;">常用食材</h2>
                    <p class="text-muted small mb-0">点开就能看到用到它的菜谱。</p>
                </div>
                @if ($search !== '')
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('ingredients.index') }}">清除搜索</a>
                @endif
            </div>

            @if ($ingredients->isEmpty())
                <div class="text-center py-4">
                    <p class="text-muted mb-3">没找到「{{ $search }}」这种食材，换个词试试。</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('ingredients.index') }}">看看全部食材</a>
                </div>
            @else
                <div class="ingredient-tiles">
                    @foreach ($ingredients as $item)
                        <a class="ingredient-tile" href="{{ route('ingredients.show', ['name' => $item->name]) }}">
                            <span class="ingredient-tile__name">{{ $item->name }}</span>
                            <span class="ingredient-tile__count">{{ $item->recipe_count }} 道菜用到</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="sg-section pt-0">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="mb-1" style="font-size:1.4rem;">食材店</h2>
                    <p class="text-muted small mb-0">
                        共 {{ $supplierTotal }} 家，蔬菜、肉禽、水产、调味、粮油各有专营，可以看店内实拍和实时价格。
                    </p>
                </div>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('suppliers.index') }}">
                    查看全部 {{ $supplierTotal }} 家 →
                </a>
            </div>

            @if ($suppliers->isEmpty())
                <div class="empty-state">
                    <p class="mb-3">还没有食材店入驻。</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('register') }}">以食材供应商身份入驻</a>
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    @foreach ($suppliers as $supplier)
                        <x-supplier-card :supplier="$supplier" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
