<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'city', 'district', 'address', 'landmark', 'lat', 'lng', 'phone',
        'hours', 'category', 'delivery', 'rating', 'cover', 'intro', 'features',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'rating' => 'float',
        ];
    }

    /** 店铺负责人（食材供应商账号） */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** 在售商品 */
    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::class)->orderBy('sort');
    }

    /** 主推商品 */
    public function signatureProducts(): HasMany
    {
        return $this->hasMany(SupplierProduct::class)->where('is_signature', true)->orderBy('sort');
    }

    /** 店内相册 */
    public function images(): HasMany
    {
        return $this->hasMany(SupplierImage::class)->orderBy('sort');
    }

    /** 厨友对这家店的留言 */
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
}
