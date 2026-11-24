@extends('layouts.app')

@section('title', '写食记 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="page-heading mb-4">
                    <h1>写一篇食记</h1>
                    <p>可以用第一人称随便写：怎么想到做这道菜、哪一步最容易翻车、最后味道怎么样。</p>
                </div>

                <form method="POST" action="{{ route('account.notes.store') }}" enctype="multipart/form-data" class="sg-card" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="title">标题</label>
                        <input type="text" name="title" id="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="比如：第三次做红烧肉，终于不柴了"
                               maxlength="60" required>
                        @error('title')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="content">正文</label>
                        <textarea name="content" id="content" rows="12"
                                  class="form-control @error('content') is-invalid @enderror"
                                  placeholder="
比如：
周末买的五花肉偏瘦，煸炒的时候油出得少，后面加了小半碗热水慢慢焖。
冰糖这次提前炒到枣红色再下肉，颜色比前两次都亮。
收汁前尝了尝，比上次咸，下次酱油减到两勺。">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">支持直接换行分段，10 到 5000 字。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="cover">封面图（选填）</label>
                        <input type="file" name="cover" id="cover"
                               class="form-control @error('cover') is-invalid @enderror"
                               accept="image/png,image/jpeg,image/webp">
                        @error('cover')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">成品照、翻车现场都行，jpg、png、webp，不超过 4MB。</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('account.notes') }}" class="btn btn-outline-secondary px-4">取消</a>
                        <button class="btn btn-primary px-4" type="submit">发布食记</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
