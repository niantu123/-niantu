@extends('layouts.app')

@section('title', '上传作品 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="mb-4">
                    <a href="{{ route('account.works') }}" class="small text-secondary">← 返回我的作品</a>
                    <h1 class="mt-2 mb-1" style="font-size:1.9rem;">上传作品</h1>
                    <p class="text-muted mb-0">写清楚用量和步骤，别人照着做才不会翻车。</p>
                </div>

                <form method="POST" action="{{ route('account.works.store') }}" enctype="multipart/form-data" class="sg-card">
                    @csrf

                    <h2 class="mb-3" style="font-size:1.15rem;">基本信息</h2>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="title">菜名</label>
                            <input type="text" name="title" id="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" placeholder="比如：外婆的梅干菜烧肉" maxlength="40" required>
                            @error('title')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="category">菜系分类</label>
                            <select name="category" id="category"
                                    class="form-select @error('category') is-invalid @enderror" required>
                                @foreach ($categories as $item)
                                    <option value="{{ $item }}" @selected(old('category') === $item)>{{ $item }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="intro">简介</label>
                            <textarea name="intro" id="intro" rows="2"
                                      class="form-control @error('intro') is-invalid @enderror"
                                      placeholder="一句话说清它的味道和适合什么时候做，10–200 字"
                                      maxlength="200" required>{{ old('intro') }}</textarea>
                            @error('intro')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="prep_minutes">准备（分钟）</label>
                            <input type="number" name="prep_minutes" id="prep_minutes"
                                   class="form-control @error('prep_minutes') is-invalid @enderror"
                                   value="{{ old('prep_minutes', 10) }}" min="0" max="600">
                            @error('prep_minutes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="cook_minutes">烹饪（分钟）</label>
                            <input type="number" name="cook_minutes" id="cook_minutes"
                                   class="form-control @error('cook_minutes') is-invalid @enderror"
                                   value="{{ old('cook_minutes', 20) }}" min="0" max="600">
                            @error('cook_minutes')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="servings">份量（人份）</label>
                            <input type="number" name="servings" id="servings"
                                   class="form-control @error('servings') is-invalid @enderror"
                                   value="{{ old('servings', 2) }}" min="1" max="20">
                            @error('servings')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label" for="difficulty">难度</label>
                            <select name="difficulty" id="difficulty"
                                    class="form-select @error('difficulty') is-invalid @enderror">
                                @foreach ($difficulties as $item)
                                    <option value="{{ $item }}" @selected(old('difficulty', '简单') === $item)>{{ $item }}</option>
                                @endforeach
                            </select>
                            @error('difficulty')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cover">成品图</label>
                            <input type="file" name="cover" id="cover"
                                   class="form-control @error('cover') is-invalid @enderror"
                                   accept="image/png,image/jpeg,image/webp">
                            @error('cover')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">jpg、png、webp，不超过 4MB；不上传就用默认封面。</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cover_url">或填写图片地址</label>
                            <input type="url" name="cover_url" id="cover_url"
                                   class="form-control @error('cover_url') is-invalid @enderror"
                                   value="{{ old('cover_url') }}" placeholder="https://...">
                            @error('cover_url')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">没有图片文件时，可以贴一个网络图片链接。</div>
                        </div>
                    </div>

                    <hr class="my-4" style="border-color:var(--sg-line);">

                    <h2 class="mb-3" style="font-size:1.15rem;">食材</h2>
                    <label class="form-label" for="ingredients">每行一种食材，格式：食材名 用量</label>
                    <textarea name="ingredients" id="ingredients" rows="6"
                              class="form-control @error('ingredients') is-invalid @enderror"
                              placeholder="五花肉 500 克&#10;梅干菜 80 克&#10;冰糖 20 克&#10;生抽 2 汤匙" required>{{ old('ingredients') }}</textarea>
                    @error('ingredients')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-text">食材名和用量用空格、逗号或竖线隔开都可以，没写用量的默认「适量」。</div>

                    <hr class="my-4" style="border-color:var(--sg-line);">

                    <h2 class="mb-3" style="font-size:1.15rem;">步骤</h2>
                    <label class="form-label" for="steps">每行一步，按顺序写</label>
                    <textarea name="steps" id="steps" rows="8"
                              class="form-control @error('steps') is-invalid @enderror"
                              placeholder="五花肉切三厘米见方，冷水下锅焯去血沫&#10;锅里放冰糖小火炒到枣红色&#10;下肉块翻炒上色，加生抽和老抽&#10;加热水没过肉，小火炖 50 分钟&#10;放入泡好的梅干菜，再炖 20 分钟收汁" required>{{ old('steps') }}</textarea>
                    @error('steps')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <span class="text-muted small">发布后可以在「我的作品」里删除。</span>
                        <button class="btn btn-primary px-4" type="submit">发布作品</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
