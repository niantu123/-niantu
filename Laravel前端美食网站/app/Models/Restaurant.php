<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'city', 'district', 'address', 'landmark', 'lat', 'lng', 'phone',
        'hours', 'accepts_booking', 'booking_notice', 'avg_price', 'rating', 'cuisine',
        'cover', 'intro', 'features',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'rating' => 'float',
            'accepts_booking' => 'boolean',
        ];
    }

    /** 厨友在这家店下的预约 */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class)->orderBy('sort');
    }

    /** 店内相册（环境图轮播用） */
    public function images(): HasMany
    {
        return $this->hasMany(RestaurantImage::class)->orderBy('sort');
    }

    /** 餐厅管理员（店主账号） */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** 厨友对这家店的评价 */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    /** 厨友打分的平均分 */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->comments()->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }

    public function chefs(): HasMany
    {
        return $this->hasMany(Staff::class)->where('role', 'chef')->orderBy('sort');
    }

    public function waiters(): HasMany
    {
        return $this->hasMany(Staff::class)->where('role', 'waiter')->orderBy('sort');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort');
    }

    public function signatureDishes(): HasMany
    {
        return $this->hasMany(MenuItem::class)->where('is_signature', true)->orderBy('sort');
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class)->withPivot('price');
    }
}
