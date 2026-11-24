@extends('layouts.auth')

@section('title', '注册 · 食光')

@section('content')
    <div class="auth-card__eyebrow">Create account</div>
    <h1 class="auth-card__title">创建你的账号</h1>
    <p class="auth-card__desc">注册后可以收藏菜谱、建立自己的菜单，也能把做好的菜分享给厨友。</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label d-block">注册身份</label>
            <div class="role-picker">
                <label class="role-option">
                    <input type="radio" name="role" value="user"
                           {{ old('role', 'user') === 'user' ? 'checked' : '' }}>
                    <span class="role-option__body">
                        <span class="role-option__title">普通用户</span>
                        <span class="role-option__desc">收藏菜谱、上传自己的作品、给餐厅和菜写评价</span>
                    </span>
                </label>
                <label class="role-option">
                    <input type="radio" name="role" value="restaurant_admin"
                           {{ old('role') === 'restaurant_admin' ? 'checked' : '' }}>
                    <span class="role-option__body">
                        <span class="role-option__title">餐厅管理员</span>
                        <span class="role-option__desc">在普通用户的基础上，还能管理自己的餐厅资料与菜单</span>
                    </span>
                </label>
                <label class="role-option role-option--wide">
                    <input type="radio" name="role" value="supplier"
                           {{ old('role') === 'supplier' ? 'checked' : '' }}>
                    <span class="role-option__body">
                        <span class="role-option__title">食材供应商</span>
                        <span class="role-option__desc">在普通用户的基础上，还能管理自己的食材店、在售商品和店内图片</span>
                    </span>
                </label>
            </div>
            @error('role')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="name">账号</label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   class="form-control form-control-lg @error('name') is-invalid @enderror"
                   placeholder="2–20 位，中文、字母、数字或下划线"
                   autocomplete="username"
                   maxlength="20"
                   required
                   autofocus>
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <div class="form-text">账号用于登录，也可以用邮箱登录。</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">邮箱</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                   placeholder="you@example.com"
                   autocomplete="email"
                   required>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">密码</label>
            <div class="input-group">
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control form-control-lg @error('password') is-invalid @enderror"
                       placeholder="至少 6 位"
                       autocomplete="new-password"
                       required>
                <button class="btn btn-eye" type="button" data-toggle-password="#password">显示</button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <div class="form-text">建议字母加数字，别用生日和手机号。</div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password_confirmation">确认密码</label>
            <input type="password"
                   id="password_confirmation"
                   name="password_confirmation"
                   class="form-control form-control-lg"
                   placeholder="再输入一次"
                   autocomplete="new-password"
                   required>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox"
                   id="agree"
                   name="agree"
                   value="1"
                   class="form-check-input @error('agree') is-invalid @enderror"
                   {{ old('agree') ? 'checked' : '' }}>
            <label class="form-check-label small text-secondary" for="agree">
                我已阅读并同意
                <a href="#" class="link-primary">《用户协议》</a>与<a href="#" class="link-primary">《隐私政策》</a>
            </label>
            @error('agree')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">注册并进入</button>
    </form>

    <p class="auth-alt text-center mt-4 mb-0">
        已经有账号了？<a href="{{ route('login') }}">直接登录</a>
    </p>
@endsection
