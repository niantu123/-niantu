@extends('layouts.app')

@section('title', $note->title.' · 食记 · 食光')

@section('content')
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-3" style="font-size:.8125rem;">
            <a href="{{ route('notes.index') }}" style="color:var(--sg-muted);">食记</a>
            <span class="mx-1" style="color:#c9bfb0;">/</span>
            <span class="text-muted">{{ $note->title }}</span>
        </nav>

        <div class="row g-4 g-lg-5">
            <div class="col-lg-8">
                <article class="note-article">
                    <header class="mb-4">
                        <h1 class="note-article__title">{{ $note->title }}</h1>
                        <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
                            @if ($note->user)
                                <x-user-avatar :user="$note->user" />
                            @else
                                <div class="avatar-circle">厨</div>
                            @endif
                            <span class="fw-medium">{{ $note->user?->name ?? '厨友' }}</span>
                            <span class="text-muted" style="font-size:.8125rem;">
                                发布于 {{ $note->created_at->format('Y 年 n 月 j 日') }}
                            </span>
                        </div>
                    </header>

                    @if ($note->cover)
                        <img class="note-article__cover mb-4" src="{{ $note->cover }}" alt="{{ $note->title }}">
                    @endif

                    <div class="note-article__body">
                        {!! nl2br(e($note->content)) !!}
                    </div>
                </article>

                <section class="mt-5 pt-4 border-top" id="comments" style="border-color:var(--sg-line)!important;">
                    <h2 class="mb-1" style="font-size:1.3rem;">厨友评论</h2>
                    <p class="text-muted small mb-4">共 {{ $comments->count() }} 条，做菜遇到的问题也可以在这里问。</p>

                    @auth
                        <form method="POST" action="{{ route('comments.note.store', $note) }}" class="ingredient-card mb-4">
                            @csrf
                            <label class="form-label" for="content">说点什么</label>
                            <textarea name="content" id="content" rows="3"
                                      class="form-control @error('content') is-invalid @enderror"
                                      placeholder="交流火候、调味，或者给作者鼓鼓劲">{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="text-muted small">以「{{ auth()->user()->name }}」的身份发布</span>
                                <button class="btn btn-primary px-4" type="submit">发布评论</button>
                            </div>
                        </form>
                    @else
                        <div class="ingredient-card mb-4 text-center py-4">
                            <p class="mb-3 text-secondary">登录后就能评论这篇食记。</p>
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
                                        <span class="fw-medium">{{ $comment->nickname }}</span>
                                        <span class="text-muted" style="font-size:.75rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="mb-0 mt-2 text-secondary" style="line-height:1.85;">{{ $comment->content }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">还没有评论，来做第一个交流的人吧。</p>
                    @endforelse
                </section>
            </div>

            <div class="col-lg-4">
                <div class="ingredient-card mb-4">
                    <h2 class="mb-3" style="font-size:1.15rem;">关于这篇食记</h2>
                    <p class="text-muted small mb-0" style="line-height:1.9;">
                        食记由厨友自行发布，做法和用量仅供参考。
                        想看完整菜谱，可以到<a href="{{ route('recipes.index') }}">菜谱库</a>按菜名找一找。
                    </p>
                </div>

                <div class="ingredient-card">
                    <h2 class="mb-3" style="font-size:1.15rem;">写下你自己的食记</h2>
                    <p class="text-muted small mb-3">上一次下厨有什么发现？成功的小技巧、失败的教训都算。</p>
                    @auth
                        <a href="{{ route('account.notes.create') }}" class="btn btn-outline-secondary btn-sm w-100">写一篇食记</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm w-100">登录后写食记</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
@endsection
