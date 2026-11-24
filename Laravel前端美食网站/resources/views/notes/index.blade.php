@extends('layouts.app')

@section('title', '食记 · 食光')

@section('content')
    <section class="sg-page-head">
        <div class="container">
            <div class="row align-items-end g-3">
                <div class="col-lg-8">
                    <h1>食记</h1>
                    <p>烧糊的、翻车的、惊艳的，都值得记下来。这里是厨友们的下厨记录，欢迎在每篇下面交流。</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @auth
                        <a href="{{ route('account.notes.create') }}" class="btn btn-primary px-4">写一篇食记</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary px-4">登录后写食记</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <section class="sg-section">
        <div class="container">
            @if ($notes->isEmpty())
                <div class="empty-state">
                    <p class="mb-3">还没有人写下第一篇食记。</p>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('home') }}">先去逛逛菜谱</a>
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    @foreach ($notes as $note)
                        <div class="col">
                            <x-note-card :note="$note" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 d-flex justify-content-center">
                    {{ $notes->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
