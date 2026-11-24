@extends('layouts.app')

@section('title', '我的收藏 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">我的收藏</h1>
                        <p class="text-muted mb-0">一共收藏了 {{ $recipes->total() }} 道菜。</p>
                    </div>
                    <a href="{{ route('recipes.index') }}" class="small" style="color:var(--sg-primary);">去菜谱库逛逛 →</a>
                </div>

                @if ($recipes->isEmpty())
                    <div class="sg-card empty-state">
                        <p class="mb-3">还没有收藏任何菜谱。看到喜欢的菜，点一下「收藏」就会出现在这里。</p>
                        <a href="{{ route('recipes.index') }}" class="btn btn-primary px-4">去找菜谱</a>
                    </div>
                @else
                    <div class="row g-4">
                        @foreach ($recipes as $recipe)
                            <div class="col-12 col-sm-6">
                                <x-recipe-card :recipe="$recipe" />
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 d-flex justify-content-center">
                        {{ $recipes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
