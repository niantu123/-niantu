@extends('layouts.app')

@section('title', '我的食记 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="page-heading d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                    <div>
                        <h1>我的食记</h1>
                        <p>发布过的做菜食记都在这里，删掉就全站不可见了。</p>
                    </div>
                    <a href="{{ route('account.notes.create') }}" class="btn btn-primary px-4">写一篇食记</a>
                </div>

                <div class="sg-card">
                    @forelse ($notes as $note)
                        <div class="work-row">
                            @if ($note->cover)
                                <img src="{{ $note->cover }}" alt="{{ $note->title }}">
                            @else
                                <div class="work-row__placeholder">{{ mb_substr($note->title, 0, 1) }}</div>
                            @endif
                            <div class="min-w-0 flex-grow-1">
                                <a href="{{ route('notes.show', $note) }}" class="fw-medium text-decoration-none" style="color:var(--sg-ink);">
                                    {{ $note->title }}
                                </a>
                                <div class="text-muted" style="font-size:.8125rem;">
                                    {{ $note->comments_count ?? 0 }} 条评论 · {{ $note->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('notes.show', $note) }}">查看</a>
                                <form method="POST" action="{{ route('account.notes.destroy', $note) }}"
                                      onsubmit="return confirm('删除「{{ $note->title }}」这篇食记？');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">删除</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <p class="mb-3">还没有写过食记。上一次下厨的收获，现在记下来正合适。</p>
                            <a href="{{ route('account.notes.create') }}" class="btn btn-primary">写第一篇</a>
                        </div>
                    @endforelse
                </div>

                <div class="mt-4 d-flex justify-content-center">
                    {{ $notes->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
