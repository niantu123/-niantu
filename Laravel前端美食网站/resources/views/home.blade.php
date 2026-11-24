@extends('layouts.app')

@section('title', '食光 · 好好吃饭')

@section('content')
    <section class="sg-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <h1>今天，想吃点什么？</h1>
                    <p>从冰箱里现有的食材出发，找一道能在今天做完的菜。菜谱都经过真人试做，用量、火候、翻车点写得很清楚。</p>

                    <form class="sg-search" action="{{ route('recipes.index') }}" method="GET">
                        <input type="search" name="q" placeholder="搜菜名或食材，比如「番茄」「30 分钟」" aria-label="搜索菜谱">
                        <button class="btn btn-primary px-4" type="submit">找菜谱</button>
                    </form>

                    <div class="sg-hot">
                        按菜系找：
                        @foreach ($categories as $category)
                            <a href="{{ route('recipes.index', ['category' => $category]) }}">{{ $category }}</a>
                        @endforeach
                    </div>

                    <div class="sg-assist-hint">
                        <span class="sg-assist-hint__dot" aria-hidden="true"></span>
                        拿不定主意？
                        <a href="{{ route('assistant.index') }}">问问美食助手，报个食材或人均就行 →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="sg-section__title">今日精选</h2>
                    <p class="sg-section__sub mb-0">编辑今天试过、觉得值得推荐的家常味道。</p>
                </div>
                <a href="{{ route('recipes.index') }}" class="small" style="color:var(--sg-primary);">全部菜谱 →</a>
            </div>

            <div class="row g-4">
                @foreach ($recipes as $recipe)
                    <div class="col-12 col-sm-6 col-lg-4">
                        <x-recipe-card :recipe="$recipe" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($works->isNotEmpty())
        <section class="sg-section pt-0">
            <div class="container">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="sg-section__title">厨友作品</h2>
                        <p class="sg-section__sub mb-0">这些菜不是编辑写的，是厨友自己做完端上来的。</p>
                    </div>
                    <a href="{{ route('recipes.index', ['category' => \App\Models\Recipe::USER_CATEGORY]) }}"
                       class="small" style="color:var(--sg-primary);">看全部作品 →</a>
                </div>

                <div class="row g-4">
                    @foreach ($works as $recipe)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <x-recipe-card :recipe="$recipe" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="sg-section pt-0">
        <div class="container">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="sg-section__title">最近在探的店</h2>
                    <p class="sg-section__sub mb-0">厨师是谁、招牌是哪道、位置怎么找，都写在详情里。</p>
                </div>
                <a href="{{ route('restaurants.index') }}" class="small" style="color:var(--sg-primary);">全部探店 →</a>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                @foreach ($restaurants as $restaurant)
                    <x-restaurant-card :restaurant="$restaurant" />
                @endforeach
            </div>
        </div>
    </section>
@endsection
