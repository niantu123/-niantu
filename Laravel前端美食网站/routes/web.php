<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// 首页
Route::get('/', [HomeController::class, 'index'])->name('home');

// 菜谱
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');

// 菜谱留言（需登录）
Route::post('/recipes/{recipe}/comments', [CommentController::class, 'store'])
    ->middleware('auth')
    ->name('comments.store');

// 收藏 / 取消收藏（需登录）
Route::post('/recipes/{recipe}/favorite', [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');

// 探店
Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');
Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show'])->name('restaurants.show');

// 餐厅留言（需登录）
Route::post('/restaurants/{restaurant}/comments', [CommentController::class, 'storeRestaurant'])
    ->middleware('auth')
    ->name('comments.restaurant.store');

// 食材：常用食材 + 食材店入口
Route::get('/ingredients', [IngredientController::class, 'index'])->name('ingredients.index');

// 单样食材：看用到它的菜谱，顺便看哪家店在卖
Route::get('/ingredients/{name}', [IngredientController::class, 'show'])->name('ingredients.show');

// 食材店
Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');

// 食材店留言（需登录）
Route::post('/suppliers/{supplier}/comments', [CommentController::class, 'storeSupplier'])
    ->middleware('auth')
    ->name('comments.supplier.store');

// 食记（做菜分享）
Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
Route::get('/notes/{note}', [NoteController::class, 'show'])->name('notes.show');

// 食记评论（需登录）
Route::post('/notes/{note}/comments', [CommentController::class, 'storeNote'])
    ->middleware('auth')
    ->name('comments.note.store');

// 美食助手（游客也能聊，按会话记历史）
Route::get('/assistant', [AssistantController::class, 'index'])->name('assistant.index');
Route::post('/assistant', [AssistantController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('assistant.store');
Route::delete('/assistant', [AssistantController::class, 'clear'])->name('assistant.clear');

// 注册 / 登录（仅未登录可访问）
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// 退出登录
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// 购物车与预约（需登录）
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/restaurants/{restaurant}/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::delete('/bookings/{booking}', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // 我的订单：提交后仍可改数量和收货信息，也可取消
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::delete('/orders/{order}/destroy', [OrderController::class, 'destroy'])->name('orders.destroy');
});

// 用户中心
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');

    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('password.update');

    Route::get('/favorites', [AccountController::class, 'favorites'])->name('favorites');

    Route::get('/works', [AccountController::class, 'works'])->name('works');
    Route::get('/works/create', [AccountController::class, 'createWork'])->name('works.create');
    Route::post('/works', [AccountController::class, 'storeWork'])->name('works.store');
    Route::delete('/works/{recipe}', [AccountController::class, 'destroyWork'])->name('works.destroy');

    Route::get('/restaurant', [AccountController::class, 'restaurant'])->name('restaurant');
    Route::post('/restaurant', [AccountController::class, 'saveRestaurant'])->name('restaurant.save');
    Route::post('/restaurant/menu', [AccountController::class, 'storeMenuItem'])->name('restaurant.menu.store');
    Route::delete('/restaurant/menu/{item}', [AccountController::class, 'destroyMenuItem'])->name('restaurant.menu.destroy');
    Route::post('/restaurant/images', [AccountController::class, 'storeRestaurantImages'])->name('restaurant.images.store');
    Route::delete('/restaurant/images/{image}', [AccountController::class, 'destroyRestaurantImage'])->name('restaurant.images.destroy');

    Route::get('/supplier', [AccountController::class, 'supplier'])->name('supplier');
    Route::post('/supplier', [AccountController::class, 'saveSupplier'])->name('supplier.save');
    Route::post('/supplier/products', [AccountController::class, 'storeSupplierProduct'])->name('supplier.products.store');
    Route::delete('/supplier/products/{product}', [AccountController::class, 'destroySupplierProduct'])->name('supplier.products.destroy');
    Route::post('/supplier/images', [AccountController::class, 'storeSupplierImages'])->name('supplier.images.store');
    Route::delete('/supplier/images/{image}', [AccountController::class, 'destroySupplierImage'])->name('supplier.images.destroy');

    Route::get('/notes', [AccountController::class, 'notes'])->name('notes');
    Route::get('/notes/create', [AccountController::class, 'createNote'])->name('notes.create');
    Route::post('/notes', [AccountController::class, 'storeNote'])->name('notes.store');
    Route::delete('/notes/{note}', [AccountController::class, 'destroyNote'])->name('notes.destroy');
});
