@extends('layouts.app')

@section('title', '我的作品 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">我的作品</h1>
                        <p class="text-muted mb-0">你上传的菜谱会出现在菜谱库的「{{ \App\Models\Recipe::USER_CATEGORY }}」分类里。</p>
                    </div>
                    <a href="{{ route('account.works.create') }}" class="btn btn-primary px-4">上传新作品</a>
                </div>

                @if ($recipes->isEmpty())
                    <div class="sg-card empty-state">
                        <p class="mb-3">还没有上传过作品。把你最拿手的那道菜写下来，别人也能学着做。</p>
                        <a href="{{ route('account.works.create') }}" class="btn btn-primary px-4">上传第一道菜</a>
                    </div>
                @else
                    <div class="sg-card">
                        @foreach ($recipes as $recipe)
                            <div class="work-row">
                                <img src="{{ $recipe->cover }}" alt="{{ $recipe->title }}">

                                <div class="flex-grow-1 min-w-0">
                                    <a href="{{ route('recipes.show', $recipe) }}" class="fw-medium" style="color:var(--sg-ink);">
                                        {{ $recipe->title }}
                                    </a>
                                    <div class="text-muted mt-1" style="font-size:.8125rem;">
                                        {{ $recipe->category }} · {{ $recipe->difficulty }} · {{ $recipe->total_minutes }} 分钟
                                        · {{ $recipe->comments_count }} 条留言
                                    </div>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        发布于 {{ $recipe->created_at->format('Y-n-j') }}
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="{{ route('recipes.show', $recipe) }}" class="btn btn-sm btn-outline-secondary">查看</a>
                                    <form method="POST" action="{{ route('account.works.destroy', $recipe) }}"
                                          onsubmit="return confirm('确定删除《{{ $recipe->title }}》吗？删除后不能恢复。');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">删除</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 d-flex justify-content-center">
                        {{ $recipes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
