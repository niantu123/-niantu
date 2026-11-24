<?php

namespace App\Providers;

use App\Models\Booking;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 分页样式使用 Bootstrap 5
        Paginator::useBootstrapFive();

        // 导航头像下拉里的购物车 / 预约角标需要全局数量
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            $cartCount = 0;
            $bookingCount = 0;

            if ($user) {
                $cartCount = (int) $user->cartItems()->sum('quantity');
                $bookingCount = $user->bookings()
                    ->where('status', '!=', Booking::STATUS_CANCELLED)
                    ->whereDate('book_date', '>=', now()->toDateString())
                    ->count();
            }

            $view->with([
                'navCartCount' => $cartCount,
                'navBookingCount' => $bookingCount,
            ]);
        });
    }
}
