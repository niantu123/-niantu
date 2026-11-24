@extends('layouts.app')

@section('title', '菜谱库 · 食光')

@section('content')
    <section class="sg-page-head">
        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-6">
                    <h1>菜谱库</h1>
                    <p>{{ $recipes->total() }} 道经过试做的家常菜，从十五分钟的快手菜到需要慢炖一小时的硬菜。</p>
                </div>
                <div class="col-lg-6">
                    <form class="sg-search ms-lg-auto" action="{{ route('recipes.index') }}" method="GET">
                        @if ($category !== '')
                            <input type="hidden" name="category" value="{{ $category }}">
                        @endif
                        <input type="search" name="q" value="{{ $search }}" placeholder="搜菜名或食材，比如「番茄」" aria-label="搜索菜谱">
                        <button class="btn btn-primary px-4" type="submit">搜索</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section">
        <div class="container">
            <div class="sg-filter-bar mb-4">
                <a class="sg-filter {{ $category === '' ? 'active' : '' }}"
                   href="{{ route('recipes.index', array_filter(['q' => $search])) }}">全部</a>
                @foreach ($categories as $item)
                    <a class="sg-filter {{ $category === $item ? 'active' : '' }}"
                       href="{{ route('recipes.index', array_filter(['category' => $item, 'q' => $search])) }}">{{ $item }}</a>
                @endforeach
            </div>

            @if ($search !== '' || $category !== '')
                <p class="text-muted small mb-4">
                    找到 {{ $recipes->total() }} 条结果
                    @if ($search !== '') · 关键词「{{ $search }}」 @endif
                    @if ($category !== '') · 分类「{{ $category }}」 @endif
                </p>
            @endif

            @if ($recipes->isEmpty())
                <div class="text-center py-5">
                    <p class="text-muted mb-3">没有找到符合条件的菜谱。</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('recipes.index') }}">看看全部菜谱</a>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($recipes as $recipe)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <x-recipe-card :recipe="$recipe" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $recipes->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
