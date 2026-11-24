@extends('layouts.app')

@section('title', '探店 · 食光')

@section('content')
    <section class="sg-page-head">
        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-6">
                    <h1>探店</h1>
                    <p>{{ $restaurants->total() }} 家值得专门跑一趟的店，每家都写清楚了厨师是谁、招牌是哪道、位置怎么找。</p>
                </div>
                <div class="col-lg-6">
                    <form class="sg-search ms-lg-auto" action="{{ route('restaurants.index') }}" method="GET">
                        @if ($cuisine !== '')
                            <input type="hidden" name="cuisine" value="{{ $cuisine }}">
                        @endif
                        <input type="search" name="q" value="{{ $search }}" placeholder="搜店名、菜系或地址" aria-label="搜索餐厅">
                        <button class="btn btn-primary px-4" type="submit">搜索</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section">
        <div class="container">
            <div class="sg-filter-bar mb-4">
                <a class="sg-filter {{ $cuisine === '' ? 'active' : '' }}"
                   href="{{ route('restaurants.index', array_filter(['q' => $search])) }}">全部</a>
                @foreach ($cuisines as $item)
                    <a class="sg-filter {{ $cuisine === $item ? 'active' : '' }}"
                       href="{{ route('restaurants.index', array_filter(['cuisine' => $item, 'q' => $search])) }}">{{ $item }}</a>
                @endforeach
            </div>

            @if ($restaurants->isEmpty())
                <div class="text-center py-5">
                    <p class="text-muted mb-3">没有找到符合条件的餐厅。</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('restaurants.index') }}">看看全部餐厅</a>
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    @foreach ($restaurants as $restaurant)
                        <x-restaurant-card :restaurant="$restaurant" />
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $restaurants->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
