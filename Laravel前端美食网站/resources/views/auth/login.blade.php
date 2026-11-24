@extends('layouts.auth')

@section('title', '登录 · 食光')

@section('content')
    <div class="auth-card__eyebrow">Welcome back</div>
    <h1 class="auth-card__title">欢迎回来</h1>
    <p class="auth-card__desc">登录后继续你的菜单，看看厨友们今天又做了什么。</p>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label" for="account">邮箱或账号</label>
            <input type="text"
                   id="account"
                   name="account"
                   value="{{ old('account') }}"
                   class="form-control form-control-lg @error('account') is-invalid @enderror"
                   placeholder="邮箱或账号，二选一"
                   autocomplete="username"
                   required
                   autofocus>
            @error('account')
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
                       placeholder="请输入密码"
                       autocomplete="current-password"
                       required>
                <button class="btn btn-eye" type="button" data-toggle-password="#password">显示</button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input type="checkbox" id="remember" name="remember" value="1" class="form-check-input"
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small text-secondary" for="remember">记住我</label>
            </div>
            <a href="#" class="small">忘记密码？</a>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">登录</button>
    </form>

    <p class="auth-alt text-center mt-4 mb-3">
        还没有账号？<a href="{{ route('register') }}">免费注册</a>
    </p>

    <div class="auth-hint">
        演示账号：<strong>demo@shiguang.test</strong>　密码：<strong>12345678</strong>
    </div>
@endsection
