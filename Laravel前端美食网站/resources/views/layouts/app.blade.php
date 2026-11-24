<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '食光 · 好好吃饭')</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
<header class="site-nav">
    <nav class="navbar navbar-expand-lg container py-2">
        <a class="navbar-brand" href="{{ route('home') }}">食光</a>

        <button class="navbar-toggler border-0 shadow-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#siteNav"
                aria-controls="siteNav" aria-expanded="false" aria-label="展开导航">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="siteNav">
            <ul class="navbar-nav me-auto ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('recipes.*') ? 'active' : '' }}" href="{{ route('recipes.index') }}">菜谱</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('ingredients.*') || request()->routeIs('suppliers.*') ? 'active' : '' }}"
                       href="{{ route('ingredients.index') }}">食材</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('restaurants.*') ? 'active' : '' }}" href="{{ route('restaurants.index') }}">探店</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('notes.*') ? 'active' : '' }}" href="{{ route('notes.index') }}">食记</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link--assistant {{ request()->routeIs('assistant.*') ? 'active' : '' }}"
                       href="{{ route('assistant.index') }}">美食助手</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 py-2 py-lg-0">
                @auth
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <x-user-avatar :user="auth()->user()" />
                            <span>{{ auth()->user()->name }}</span>
                            @if (auth()->user()->role !== 'user')
                                <span class="role-badge role-badge--{{ auth()->user()->role }}">
                                    {{ auth()->user()->role_label }}
                                </span>
                            @endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li>
                                <div class="dropdown-header" style="font-size:.75rem;">
                                    {{ auth()->user()->role_label }} · {{ auth()->user()->email }}
                                </div>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('account.index') }}">用户中心</a></li>
                            <li><a class="dropdown-item" href="{{ route('account.favorites') }}">我的收藏</a></li>
                            <li><a class="dropdown-item" href="{{ route('account.works') }}">我的作品</a></li>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('cart.index') }}">
                                    <span>购物车</span>
                                    <span class="nav-count" data-cart-badge @if ($navCartCount === 0) hidden @endif>{{ $navCartCount }}</span>
                                </a>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('orders.index') }}">我的订单</a></li>
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center" href="{{ route('bookings.index') }}">
                                    <span>我的预约</span>
                                    @if ($navBookingCount > 0)
                                        <span class="nav-count">{{ $navBookingCount }}</span>
                                    @endif
                                </a>
                            </li>
                            @if (auth()->user()->canManageRestaurant())
                                <li><a class="dropdown-item" href="{{ route('account.restaurant') }}">餐厅管理</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">退出登录</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a class="btn btn-link text-decoration-none text-secondary" href="{{ route('login') }}">登录</a>
                    <a class="btn btn-primary px-3" href="{{ route('register') }}">免费注册</a>
                @endauth
            </div>
        </div>
    </nav>
</header>

<main>
    @if (session('status'))
        <div class="container mt-4">
            <div class="alert alert-success border-0 rounded-3 mb-0">{{ session('status') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="container mt-4">
            <div class="alert alert-warning border-0 rounded-3 mb-0">{{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer mt-5">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
        <div>
            <div class="sg-serif fs-6 mb-1" style="letter-spacing:.16em;color:#23201c;">食光</div>
            <div>好好吃饭，就是好好生活。</div>
        </div>
        <div class="d-flex gap-4">
            <a href="#" class="text-secondary">关于我们</a>
            <a href="#" class="text-secondary">内容合作</a>
            <a href="#" class="text-secondary">用户协议</a>
        </div>
    </div>
</footer>
</body>
</html>
