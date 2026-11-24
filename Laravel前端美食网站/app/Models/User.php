<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** 普通用户 */
    public const ROLE_USER = 'user';

    /** 餐厅管理员：只能管理自己的餐厅 */
    public const ROLE_RESTAURANT = 'restaurant_admin';

    /** 食材供应商：只能管理自己的食材店 */
    public const ROLE_SUPPLIER = 'supplier';

    /** 站点管理员 */
    public const ROLE_ADMIN = 'admin';

    /** 角色中文名 */
    public const ROLE_LABELS = [
        self::ROLE_USER => '普通用户',
        self::ROLE_RESTAURANT => '餐厅管理员',
        self::ROLE_SUPPLIER => '食材供应商',
        self::ROLE_ADMIN => '管理员',
    ];

    /** 注册页允许自助选择的身份（管理员只能后台开通） */
    public const REGISTERABLE_ROLES = [
        self::ROLE_USER,
        self::ROLE_RESTAURANT,
        self::ROLE_SUPPLIER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'role',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRestaurantAdmin(): bool
    {
        return $this->role === self::ROLE_RESTAURANT;
    }

    public function isSupplier(): bool
    {
        return $this->role === self::ROLE_SUPPLIER;
    }

    /** 有餐厅管理权限（餐厅管理员或站点管理员） */
    public function canManageRestaurant(): bool
    {
        return in_array($this->role, [self::ROLE_RESTAURANT, self::ROLE_ADMIN], true);
    }

    /** 有食材店管理权限（食材供应商或站点管理员） */
    public function canManageSupplier(): bool
    {
        return in_array($this->role, [self::ROLE_SUPPLIER, self::ROLE_ADMIN], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_LABELS[$this->role] ?? '普通用户';
    }

    /** 头像地址，没上传时返回 null，由界面显示名字首字 */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return str_starts_with($this->avatar, 'http') ? $this->avatar : asset($this->avatar);
    }

    /** 我收藏的菜谱 */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)->latest();
    }

    /** 我上传的作品 */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class)->latest('id');
    }

    /** 我发的食记 */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->latest('id');
    }

    /** 我管理的餐厅（餐厅管理员） */
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'owner_id');
    }

    /** 我管理的食材店（食材供应商） */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'owner_id');
    }

    /** 是否收藏了某道菜谱 */
    public function hasFavorited(int $recipeId): bool
    {
        return $this->favorites()->where('recipe_id', $recipeId)->exists();
    }

    /** 我的购物车 */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /** 我的到店预约 */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest('book_date');
    }

    /** 我的食材订单 */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    /** 购物车里的商品总件数（导航角标用） */
    public function cartCount(): int
    {
        return (int) $this->cartItems()->sum('quantity');
    }
}
