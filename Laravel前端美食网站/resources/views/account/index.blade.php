@extends('layouts.app')

@section('title', '账号资料 · 食光')

@section('content')
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-3">
                @include('account._sidebar')
            </div>

            <div class="col-lg-9">
                <div class="page-heading mb-4">
                    <h1>账号资料</h1>
                    <p>这里的信息会显示在你的留言和作品上，改完记得点底部保存。</p>
                </div>

                <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')

                    {{-- 顶部身份卡：头像、账号、角色、数据概览 --}}
                    <div class="profile-hero mb-4">
                        <div class="profile-hero__main">
                            <label class="avatar-edit" for="avatar" title="点击更换头像">
                                <span class="avatar-edit__canvas" data-avatar-preview>
                                    <x-user-avatar :user="$user" size="lg" />
                                </span>
                                <span class="avatar-edit__mask">更换头像</span>
                            </label>

                            <input type="file" name="avatar" id="avatar"
                                   class="visually-hidden @error('avatar') is-invalid @enderror"
                                   accept="image/png,image/jpeg,image/webp"
                                   data-avatar-input="[data-avatar-preview]">

                            <div class="min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="profile-hero__name text-truncate">{{ $user->name }}</span>
                                    <span class="role-badge role-badge--{{ $user->role }}">{{ $user->role_label }}</span>
                                </div>
                                <div class="profile-hero__meta">
                                    {{ $user->bio ?: '还没写简介，下面可以补一句，让别人认识你。' }}
                                </div>
                                <div class="profile-hero__sub">
                                    {{ $user->email }}
                                    @if ($user->phone)
                                        · {{ $user->phone }}
                                    @endif
                                    · 加入于 {{ $user->created_at?->format('Y 年 n 月') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        @error('avatar')
                            <div class="text-danger mt-2" style="font-size:.8125rem;">{{ $message }}</div>
                        @enderror

                        <div class="profile-stats">
                            <div class="profile-stat">
                                <div class="profile-stat__num">{{ $stats['favorites'] }}</div>
                                <div class="profile-stat__label">收藏的菜谱</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat__num">{{ $stats['works'] }}</div>
                                <div class="profile-stat__label">上传的作品</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat__num">{{ $stats['notes'] }}</div>
                                <div class="profile-stat__label">写的食记</div>
                            </div>
                            <div class="profile-stat">
                                <div class="profile-stat__num">{{ $stats['comments'] }}</div>
                                <div class="profile-stat__label">发过的评价</div>
                            </div>
                        </div>
                    </div>

                    {{-- 基本资料 --}}
                    <div class="sg-card mb-4">
                        <div class="form-section">
                            <h2 class="form-section__title">基本资料</h2>
                            <p class="form-section__hint">账号名既是对外展示的名字，也是登录凭证</p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">账号名</label>
                                <input type="text" name="name" id="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}"
                                       maxlength="20" autocomplete="username" required>
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="field-note field-note--warn">
                                    可以修改，支持中文、字母、数字和下划线（2–20 字）。改完以后要用新账号名登录。
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">邮箱</label>
                                <input type="email" name="email" id="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}"
                                       autocomplete="email" required>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="field-note">用来登录和接收通知，不会公开显示。</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">手机号</label>
                                <input type="text" name="phone" id="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}"
                                       placeholder="选填，11 位手机号"
                                       maxlength="11" autocomplete="tel">
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="field-note">绑定后可用于找回账号。</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="bio">一句话简介</label>
                                <input type="text" name="bio" id="bio"
                                       class="form-control @error('bio') is-invalid @enderror"
                                       value="{{ old('bio', $user->bio) }}"
                                       maxlength="120"
                                       placeholder="比如：爱做饭，也爱吃别人做的饭。">
                                @error('bio')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="field-note d-flex justify-content-between gap-2">
                                    <span>会显示在作品和评价旁边。</span>
                                    <span data-count-for="#bio">0 / 120</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="btn btn-primary px-4" type="submit">保存资料</button>
                        </div>
                    </div>
                </form>

                {{-- 修改密码 --}}
                <form method="POST" action="{{ route('account.password.update') }}" class="sg-card" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-section">
                        <h2 class="form-section__title">修改密码</h2>
                        <p class="form-section__hint">改完请记好，下次登录要用新密码</p>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="current_password">当前密码</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="先验证一下本人"
                                   autocomplete="current-password" required>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="password">新密码</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="至少 6 位"
                                   autocomplete="new-password" required>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="password_confirmation">确认新密码</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control"
                                   placeholder="再输一次"
                                   autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-outline-secondary px-4" type="submit">更新密码</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
