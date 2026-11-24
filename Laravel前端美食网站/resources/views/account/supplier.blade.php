@extends('layouts.app')

@section('title', '食材店管理 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-2">
                    <div>
                        <h1 class="mb-1" style="font-size:1.9rem;">食材店管理</h1>
                        <p class="text-muted mb-0">
                            @if ($supplier)
                                正在管理「{{ $supplier->name }}」，改完保存就同步到食材店页面。
                            @else
                                还没有绑定食材店，填好下面的资料就能开始上架。
                            @endif
                        </p>
                    </div>
                    @if ($supplier)
                        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary btn-sm">查看店铺页</a>
                    @endif
                </div>

                @if ($shops->isNotEmpty())
                    <div class="sg-card mb-4 py-3">
                        <form method="GET" action="{{ route('account.supplier') }}" class="d-flex align-items-center gap-3 flex-wrap">
                            <label class="form-label mb-0 small text-muted" for="shop">以管理员身份切换店铺</label>
                            <select name="shop" id="shop" class="form-select form-select-sm" style="max-width:300px;"
                                    onchange="this.form.submit()">
                                @foreach ($shops as $shop)
                                    <option value="{{ $shop->id }}" @selected($supplier && $supplier->id === $shop->id)>
                                        {{ $shop->name }}（{{ $shop->category }}）
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-muted" style="font-size:.75rem;">全站共 {{ $shops->count() }} 家食材店，管理员可以维护任意一家。</span>
                        </form>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success border-0 rounded-3">{{ session('status') }}</div>
                @endif

                @if (! $supplier)
                    <div class="alert alert-warning border-0 rounded-3">
                        创建食材店后，你才能上架商品、上传店内图片、收到厨友评价。资料填得越完整，店铺页看起来越可信。
                    </div>
                @endif

                <form method="POST" action="{{ route('account.supplier.save') }}" enctype="multipart/form-data" class="sg-card mb-4">
                    @csrf
                    @if ($shops->isNotEmpty())
                        <input type="hidden" name="shop" value="{{ $supplier?->id }}">
                    @endif

                    <h2 class="mb-3" style="font-size:1.15rem;">基本资料</h2>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="name">店铺名称</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $supplier?->name) }}" maxlength="60" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-5">
                            <label class="form-label" for="category">主营类别</label>
                            <input type="text" name="category" id="category"
                                   class="form-control @error('category') is-invalid @enderror"
                                   value="{{ old('category', $supplier?->category) }}"
                                   placeholder="比如：蔬菜、水产、调味、粮油" maxlength="30" required>
                            @error('category')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="city">城市</label>
                            <input type="text" name="city" id="city"
                                   class="form-control @error('city') is-invalid @enderror"
                                   value="{{ old('city', $supplier?->city ?? '上海') }}" maxlength="30" required>
                            @error('city')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="district">区县</label>
                            <input type="text" name="district" id="district"
                                   class="form-control @error('district') is-invalid @enderror"
                                   value="{{ old('district', $supplier?->district) }}" maxlength="30" required>
                            @error('district')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="phone">电话</label>
                            <input type="text" name="phone" id="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $supplier?->phone) }}" maxlength="30">
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="address">详细地址</label>
                            <input type="text" name="address" id="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $supplier?->address) }}"
                                   placeholder="比如：永康路菜市场 12 号摊位" maxlength="120" required>
                            @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="landmark">怎么找到你</label>
                            <input type="text" name="landmark" id="landmark"
                                   class="form-control @error('landmark') is-invalid @enderror"
                                   value="{{ old('landmark', $supplier?->landmark) }}"
                                   placeholder="比如：菜市场东门进去第三个摊位" maxlength="80">
                            @error('landmark')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="hours">营业时间</label>
                            <input type="text" name="hours" id="hours"
                                   class="form-control @error('hours') is-invalid @enderror"
                                   value="{{ old('hours', $supplier?->hours ?? '06:00–19:00') }}" maxlength="60" required>
                            @error('hours')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="delivery">配送与起批</label>
                            <input type="text" name="delivery" id="delivery"
                                   class="form-control @error('delivery') is-invalid @enderror"
                                   value="{{ old('delivery', $supplier?->delivery) }}"
                                   placeholder="比如：市区满 50 元免运费，凌晨可送" maxlength="60">
                            @error('delivery')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="features">标签</label>
                            <input type="text" name="features" id="features"
                                   class="form-control @error('features') is-invalid @enderror"
                                   value="{{ old('features', is_array($supplier?->features) ? implode('、', $supplier->features) : '') }}"
                                   placeholder="用逗号或顿号分隔，比如：当日到货、可开票、支持批发" maxlength="200">
                            @error('features')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="intro">店铺介绍</label>
                            <textarea name="intro" id="intro" rows="4"
                                      class="form-control @error('intro') is-invalid @enderror"
                                      maxlength="1000" required
                                      placeholder="货源从哪来、什么时段最新鲜、适合哪类买家">{{ old('intro', $supplier?->intro) }}</textarea>
                            @error('intro')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="cover">封面图</label>
                            <input type="file" name="cover" id="cover"
                                   class="form-control @error('cover') is-invalid @enderror"
                                   accept="image/png,image/jpeg,image/webp">
                            @error('cover')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
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
                            {{ $supplier ? '保存修改' : '创建食材店' }}
                        </button>
                    </div>
                </form>

                @if ($supplier)
                    <div class="sg-card mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div>
                                <h2 class="mb-1" style="font-size:1.15rem;">商品管理</h2>
                                <p class="text-muted small mb-0">共 {{ $products->count() }} 款，其中主推 {{ $signature->count() }} 款。</p>
                            </div>
                        </div>

                        @if ($products->isNotEmpty())
                            <div class="table-responsive mb-4">
                                <table class="table align-middle mb-0" style="font-size:.9rem;">
                                    <thead>
                                        <tr class="text-muted" style="font-size:.78rem;">
                                            <th>商品</th>
                                            <th>分类</th>
                                            <th>规格</th>
                                            <th class="text-end">价格</th>
                                            <th class="text-center">主推</th>
                                            <th class="text-end">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $product)
                                            <tr>
                                                <td>
                                                    {{ $product->name }}
                                                    @if ($product->intro)
                                                        <div class="text-muted" style="font-size:.75rem;">{{ $product->intro }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $product->category }}</td>
                                                <td class="text-muted">{{ $product->unit }}</td>
                                                <td class="text-end menu-price">¥{{ rtrim(rtrim(number_format($product->price, 2, '.', ''), '0'), '.') }}</td>
                                                <td class="text-center">
                                                    @if ($product->is_signature)
                                                        <span class="sg-chip" style="font-size:.7rem;">主推</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST"
                                                          action="{{ route('account.supplier.products.destroy', $product) }}"
                                                          onsubmit="return confirm('下架「{{ $product->name }}」？');">
                                                        @csrf
                                                        @method('DELETE')
                                                        @if ($shops->isNotEmpty())
                                                            <input type="hidden" name="shop" value="{{ $supplier->id }}">
                                                        @endif
                                                        <button class="btn btn-sm btn-outline-danger" type="submit">下架</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted small">还没有商品，用下面的表单上架第一件。</p>
                        @endif

                        <form method="POST" action="{{ route('account.supplier.products.store') }}">
                            @csrf
                            @if ($shops->isNotEmpty())
                                <input type="hidden" name="shop" value="{{ $supplier->id }}">
                            @endif

                            <h3 class="mb-3" style="font-size:.95rem;">上架一件商品</h3>

                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label class="form-label" for="product_name">商品名</label>
                                    <input type="text" name="name" id="product_name"
                                           class="form-control form-control-sm @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" maxlength="60" required>
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="product_category">分类</label>
                                    <select name="category" id="product_category" class="form-select form-select-sm">
                                        @foreach (['蔬菜', '肉禽', '水产', '蛋奶', '豆制品', '调味', '粮油', '烘焙'] as $cat)
                                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="product_unit">规格</label>
                                    <input type="text" name="unit" id="product_unit"
                                           class="form-control form-control-sm @error('unit') is-invalid @enderror"
                                           value="{{ old('unit', '500g') }}" maxlength="20" required>
                                    @error('unit')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="product_price">价格</label>
                                    <input type="number" name="price" id="product_price" step="0.01" min="0"
                                           class="form-control form-control-sm @error('price') is-invalid @enderror"
                                           value="{{ old('price') }}" required>
                                    @error('price')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="product_intro">说明</label>
                                    <input type="text" name="intro" id="product_intro"
                                           class="form-control form-control-sm @error('intro') is-invalid @enderror"
                                           value="{{ old('intro') }}" placeholder="产地、卖点" maxlength="140">
                                    @error('intro')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-6 col-md-1 d-flex flex-column align-items-start">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="is_signature"
                                               id="product_signature" value="1" @checked(old('is_signature'))>
                                        <label class="form-check-label" for="product_signature" style="font-size:.78rem;">主推</label>
                                    </div>
                                    <button class="btn btn-sm btn-primary w-100" type="submit">上架</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="sg-card mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div>
                                <h2 class="mb-1" style="font-size:1.15rem;">店内图片</h2>
                                <p class="text-muted small mb-0">摊位、货架、冷柜的照片，顾客在店铺页会以轮播图看到。已上传 {{ $supplier->images->count() }} 张。</p>
                            </div>
                        </div>

                        @if ($supplier->images->isNotEmpty())
                            <div class="gallery-grid mb-4">
                                @foreach ($supplier->images as $image)
                                    <div class="gallery-item">
                                        <img src="{{ $image->path }}" alt="{{ $image->caption ?? '店内图片' }}" loading="lazy">
                                        @if ($image->caption)
                                            <div class="gallery-item__caption">{{ $image->caption }}</div>
                                        @endif
                                        <form method="POST" action="{{ route('account.supplier.images.destroy', $image) }}"
                                              class="gallery-item__del" onsubmit="return confirm('删除这张图片？');">
                                            @csrf
                                            @method('DELETE')
                                            @if ($shops->isNotEmpty())
                                                <input type="hidden" name="shop" value="{{ $supplier->id }}">
                                            @endif
                                            <button class="btn btn-sm btn-light" type="submit" title="删除">×</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('account.supplier.images.store') }}" enctype="multipart/form-data">
                            @csrf
                            @if ($shops->isNotEmpty())
                                <input type="hidden" name="shop" value="{{ $supplier->id }}">
                            @endif

                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label" for="supplier_images">选择图片（可多选，一次最多 6 张）</label>
                                    <input type="file" name="images[]" id="supplier_images" multiple
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
                                    <label class="form-label" for="caption">图片说明（选填）</label>
                                    <input type="text" name="caption" id="caption"
                                           class="form-control form-control-sm @error('caption') is-invalid @enderror"
                                           value="{{ old('caption') }}" maxlength="40" placeholder="比如：清晨的蔬菜摊">
                                    @error('caption')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-2">
                                    <button class="btn btn-sm btn-primary w-100" type="submit">上传</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    @if ($comments->isNotEmpty())
                        <div class="sg-card">
                            <h2 class="mb-3" style="font-size:1.15rem;">最近的评价</h2>
                            @foreach ($comments as $comment)
                                <div class="comment-item">
                                    <div class="d-flex gap-3">
                                        @if ($comment->user)
                                            <x-user-avatar :user="$comment->user" size="circle" />
                                        @else
                                            <div class="avatar-circle">{{ mb_substr($comment->nickname, 0, 1) }}</div>
                                        @endif
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <div>
                                                    <span class="fw-medium">{{ $comment->nickname }}</span>
                                                    <span class="stars ms-2">{{ str_repeat('★', $comment->rating) }}</span>
                                                </div>
                                                <span class="text-muted" style="font-size:.75rem;">{{ $comment->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="mb-0 mt-2 text-secondary" style="line-height:1.85;">{{ $comment->content }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
