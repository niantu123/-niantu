@extends('layouts.app')

@section('title', '餐厅管理 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">餐厅管理</h1>
                        <p class="text-muted mb-0">
                            @if ($restaurant)
                                正在管理「{{ $restaurant->name }}」，改完保存就同步到探店页。
                            @else
                                还没有绑定餐厅，填好下面的资料就能开张。
                            @endif
                        </p>
                    </div>
                    @if ($restaurant)
                        <a href="{{ route('restaurants.show', $restaurant) }}" class="btn btn-outline-secondary btn-sm">查看店铺页</a>
                    @endif
                </div>

                @if ($shops->isNotEmpty())
                    <div class="sg-card mb-4 py-3">
                        <form method="GET" action="{{ route('account.restaurant') }}" class="d-flex align-items-center gap-3 flex-wrap">
                            <label class="form-label mb-0 small text-muted" for="shop">以管理员身份切换餐厅</label>
                            <select name="shop" id="shop" class="form-select form-select-sm" style="max-width:300px;"
                                    onchange="this.form.submit()">
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}" @selected($restaurant && $restaurant->id === $shop->id)>
                                        {{ $shop->name }}（{{ $shop->cuisine }}）
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-muted" style="font-size:.75rem;">全站共 {{ $shops->count() }} 家餐厅，管理员可以维护任意一家。</span>
                        </form>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success border-0 rounded-3">{{ session('status') }}</div>
                @endif

                @if (! $restaurant)
                    <div class="alert alert-warning border-0 rounded-3">
                        创建餐厅后，你才能添加菜单、收到顾客评价。资料填得越完整，探店页看起来越可信。
                    </div>
                @endif

                <form method="POST" action="{{ route('account.restaurant.save') }}" enctype="multipart/form-data" class="sg-card mb-4">
                    @csrf
                    @if ($shops->isNotEmpty())
                        <input type="hidden" name="shop" value="{{ $restaurant?->id }}">
                    @endif

                    <h2 class="mb-3" style="font-size:1.15rem;">基本资料</h2>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="name">餐厅名称</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $restaurant?->name) }}" maxlength="60" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="cuisine">菜系</label>
                            <input type="text" name="cuisine" id="cuisine"
                                   class="form-control @error('cuisine') is-invalid @enderror"
                                   value="{{ old('cuisine', $restaurant?->cuisine) }}"
                                   placeholder="比如：本帮菜、川菜、面食" maxlength="30" required>
                            @error('cuisine')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="city">城市</label>
                            <input type="text" name="city" id="city"
                                   class="form-control @error('city') is-invalid @enderror"
                                   value="{{ old('city', $restaurant?->city ?? '上海') }}" maxlength="30" required>
                            @error('city')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="district">区县</label>
                            <input type="text" name="district" id="district"
                                   class="form-control @error('district') is-invalid @enderror"
                                   value="{{ old('district', $restaurant?->district) }}"
                                   placeholder="比如：静安区" maxlength="30" required>
                            @error('district')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="avg_price">人均消费（元）</label>
                            <input type="number" name="avg_price" id="avg_price"
                                   class="form-control @error('avg_price') is-invalid @enderror"
                                   value="{{ old('avg_price', $restaurant?->avg_price ?? 80) }}" min="1" max="9999" required>
                            @error('avg_price')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="address">详细地址</label>
                            <input type="text" name="address" id="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $restaurant?->address) }}" maxlength="120" required>
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="phone">电话</label>
                            <input type="text" name="phone" id="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $restaurant?->phone) }}" maxlength="30">
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="hours">营业时间</label>
                            <input type="text" name="hours" id="hours"
                                   class="form-control @error('hours') is-invalid @enderror"
                                   value="{{ old('hours', $restaurant?->hours) }}"
                                   placeholder="比如：11:00–14:00 / 17:00–21:30" maxlength="60" required>
                            @error('hours')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="landmark">怎么找</label>
                            <input type="text" name="landmark" id="landmark"
                                   class="form-control @error('landmark') is-invalid @enderror"
                                   value="{{ old('landmark', $restaurant?->landmark) }}"
                                   placeholder="比如：地铁 2 号线出口走 300 米" maxlength="80">
                            @error('landmark')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="intro">餐厅介绍</label>
                            <textarea name="intro" id="intro" rows="4"
                                      class="form-control @error('intro') is-invalid @enderror"
                                      maxlength="1000" required>{{ old('intro', $restaurant?->intro) }}</textarea>
                            @error('intro')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="features">特色标签</label>
                            <input type="text" name="features" id="features"
                                   class="form-control @error('features') is-invalid @enderror"
                                   value="{{ old('features', $restaurant?->features ? implode('、', $restaurant->features) : '') }}"
                                   placeholder="可预订、有包间、夜宵，用顿号或逗号分隔" maxlength="200">
                            @error('features')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <div class="booking-switch">
                                <div class="flex-grow-1">
                                    <label class="form-label mb-1" for="accepts_booking">开放在线预约</label>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        打开后，探店页会出现「选日期预约」按钮，厨友可以选日期、时段和人数订位；
                                        关掉则只显示电话，让人直接打给你。
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="accepts_booking" id="accepts_booking" value="1"
                                           @checked(old('accepts_booking', $restaurant?->accepts_booking))>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="booking_notice">预约须知</label>
                            <input type="text" name="booking_notice" id="booking_notice"
                                   class="form-control @error('booking_notice') is-invalid @enderror"
                                   value="{{ old('booking_notice', $restaurant?->booking_notice) }}"
                                   placeholder="周末建议提前一天订位，座位保留 15 分钟" maxlength="120">
                            @error('booking_notice')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cover">门面或环境图</label>
                            <input type="file" name="cover" id="cover"
                                   class="form-control @error('cover') is-invalid @enderror"
                                   accept="image/png,image/jpeg,image/webp">
                            @error('cover')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">jpg、png、webp，不超过 6MB。</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cover_url">或填写图片地址</label>
                            <input type="url" name="cover_url" id="cover_url"
                                   class="form-control @error('cover_url') is-invalid @enderror"
                                   value="{{ old('cover_url') }}" placeholder="https://...">
                            @error('cover_url')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary px-4" type="submit">
                            {{ $restaurant ? '保存修改' : '创建餐厅' }}
                        </button>
                    </div>
                </form>

                @if ($restaurant)
                    <div class="sg-card mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div>
                                <h2 class="mb-1" style="font-size:1.15rem;">菜单管理</h2>
                                <p class="text-muted small mb-0">共 {{ $menu->count() }} 道，其中招牌菜 {{ $dishes->count() }} 道。</p>
                            </div>
                        </div>

                        @if ($menu->isNotEmpty())
                            <div class="table-responsive mb-4">
                                <table class="table align-middle mb-0" style="font-size:.9rem;">
                                    <thead>
                                        <tr class="text-muted" style="font-size:.78rem;">
                                            <th>菜名</th>
                                            <th>分类</th>
                                            <th class="text-end">价格</th>
                                            <th class="text-center">招牌</th>
                                            <th class="text-end">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($menu as $item)
                                            <tr>
                                                <td>
                                                    {{ $item->name }}
                                                    @if ($item->intro)
                                                        <div class="text-muted" style="font-size:.75rem;">{{ $item->intro }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $item->category }}</td>
                                                <td class="text-end menu-price">¥{{ number_format($item->price, 0) }}</td>
                                                <td class="text-center">
                                                    @if ($item->is_signature)
                                                        <span class="sg-chip" style="font-size:.7rem;">招牌</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST"
                                                          action="{{ route('account.restaurant.menu.destroy', $item) }}"
                                                          onsubmit="return confirm('删除「{{ $item->name }}」这道菜？');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" type="submit">删除</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small">还没有菜品，用下面的表单添加第一道。</p>
                        @endif

                        <form method="POST" action="{{ route('account.restaurant.menu.store') }}">
                            @csrf
                            @if ($shops->isNotEmpty())
                                <input type="hidden" name="shop" value="{{ $restaurant->id }}">
                            @endif
                            <h3 class="mb-3" style="font-size:.95rem;">添加一道菜</h3>

                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="dish_name">菜名</label>
                                    <input type="text" name="name" id="dish_name"
                                           class="form-control form-control-sm @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" maxlength="60" required>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="dish_category">分类</label>
                                    <select name="category" id="dish_category" class="form-select form-select-sm">
                                        @foreach (['前菜', '主菜', '汤羹', '主食', '甜品', '饮品'] as $cat)
                                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="dish_price">价格</label>
                                    <input type="number" name="price" id="dish_price"
                                           class="form-control form-control-sm @error('price') is-invalid @enderror"
                                           value="{{ old('price') }}" min="0" max="9999" step="1" required>
                                    @error('price')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label" for="dish_intro">一句卖点</label>
                                    <input type="text" name="intro" id="dish_intro"
                                           class="form-control form-control-sm"
                                           value="{{ old('intro') }}" maxlength="140"
                                           placeholder="比如：每日限二十份">
                                </div>

                                <div class="col-6 col-md-2 d-flex align-items-center gap-2">
                                    <input type="checkbox" name="is_signature" id="is_signature" value="1"
                                           class="form-check-input mt-0" @checked(old('is_signature'))>
                                    <label class="form-check-label" for="is_signature" style="font-size:.85rem;">招牌菜</label>
                                </div>

                                <div class="col-6 col-md-3 ms-auto">
                                    <button class="btn btn-primary btn-sm w-100" type="submit">加入菜单</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="sg-card mb-4">
                        <h2 class="mb-1" style="font-size:1.15rem;">店内图片</h2>
                        <p class="text-muted small mb-3">传一些堂食环境、门面、包间的照片，顾客在店铺页会以轮播图看到。已上传 {{ $restaurant->images->count() }} 张。</p>

                        @if ($restaurant->images->isNotEmpty())
                            <div class="gallery-grid mb-4">
                                @foreach ($restaurant->images as $image)
                                    <div class="gallery-item">
                                        <img src="{{ $image->path }}" alt="{{ $image->caption ?? '店内图片' }}" loading="lazy">
                                        @if ($image->caption)
                                            <div class="gallery-item__caption">{{ $image->caption }}</div>
                                        @endif
                                        <form method="POST" action="{{ route('account.restaurant.images.destroy', $image) }}"
                                              onsubmit="return confirm('删除这张店内图片？');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger gallery-item__del" type="submit">删除</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small">还没有图片，用下面的表单上传第一批。</p>
                        @endif

                        <form method="POST" action="{{ route('account.restaurant.images.store') }}" enctype="multipart/form-data">
                            @csrf
                            @if ($shops->isNotEmpty())
                                <input type="hidden" name="shop" value="{{ $restaurant->id }}">
                            @endif
                            <h3 class="mb-3" style="font-size:.95rem;">上传新图片</h3>

                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label" for="gallery_images">选择图片（可多选，一次最多 6 张）</label>
                                    <input type="file" name="images[]" id="gallery_images" multiple
                                           class="form-control form-control-sm @error('images.*') is-invalid @enderror"
                                           accept="image/png,image/jpeg,image/webp">
                                    @error('images.*')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('images')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="gallery_caption">图片说明（选填，对本批生效）</label>
                                    <input type="text" name="caption" id="gallery_caption"
                                           class="form-control form-control-sm @error('caption') is-invalid @enderror"
                                           value="{{ old('caption') }}" maxlength="40" placeholder="比如：二楼卡座">
                                    @error('caption')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <button class="btn btn-primary btn-sm w-100" type="submit">上传图片</button>
                                </div>
                            </div>
                            <div class="form-text">jpg、png、webp，每张不超过 4MB。</div>
                        </form>
                    </div>

                    <div class="sg-card">
                        <h2 class="mb-1" style="font-size:1.15rem;">顾客评价</h2>
                        <p class="text-muted small mb-3">
                            @if ($comments->isEmpty())
                                还没有评价，顾客在店铺页留言后会显示在这里。
                            @else
                                最近 {{ $comments->count() }} 条，平均 {{ number_format($comments->avg('rating'), 1) }} 分。
                            @endif
                        </p>

                        @foreach ($comments as $comment)
                            <div class="comment-item">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="fw-medium">{{ $comment->nickname }}</span>
                                        <span class="stars ms-2">{{ str_repeat('★', $comment->rating) }}</span>
                                    </div>
                                    <span class="text-muted" style="font-size:.75rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mb-0 mt-2 text-secondary" style="font-size:.9rem;line-height:1.8;">{{ $comment->content }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
