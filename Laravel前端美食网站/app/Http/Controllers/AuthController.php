<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * 显示注册页
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * 处理注册：使用 DB 类把注册信息写入 users 表
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // 1. 用 DB 类先查一次，确认邮箱没被占用
        $exists = DB::table('users')->where('email', $data['email'])->exists();

        if ($exists) {
            return back()
                ->withInput($request->only('name', 'email'))
                ->withErrors(['email' => '这个邮箱已经注册过了，直接登录吧。']);
        }

        // 2. 用 DB 类插入一条用户记录，insertGetId 返回自增主键
        $userId = DB::table('users')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']), // 密码必须哈希后入库
            'role' => $data['role'], // user 普通用户 / restaurant_admin 餐厅管理员 / supplier 食材供应商
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. 注册成功后直接登录
        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        // 4. 餐厅管理员注册后直接去完善餐厅资料
        if ($data['role'] === User::ROLE_RESTAURANT) {
            return redirect()
                ->route('account.restaurant')
                ->with('status', '欢迎加入食光，'.$data['name'].'！接下来填写你的餐厅资料吧。');
        }

        // 5. 食材供应商注册后直接去完善食材店资料
        if ($data['role'] === User::ROLE_SUPPLIER) {
            return redirect()
                ->route('account.supplier')
                ->with('status', '欢迎加入食光，'.$data['name'].'！接下来填写你的食材店资料吧。');
        }

        return redirect()
            ->route('home')
            ->with('status', '欢迎加入食光，'.$data['name'].'！先去挑一道今天想做的菜吧。');
    }

    /**
     * 显示登录页
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * 处理登录：邮箱或账号都能登录，用 DB 类查询，再用 Hash::check 校验密码
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $account = trim($credentials['account']);

        $user = DB::table('users')
            ->where('email', $account)
            ->orWhere('name', $account)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('account'))
                ->withErrors(['account' => '邮箱、账号或密码不正确。']);
        }

        Auth::loginUsingId($user->id, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()
            ->intended(route('home'))
            ->with('status', '欢迎回来，'.$user->name);
    }

    /**
     * 退出登录
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', '已退出登录。');
    }
}
