<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    /** 分类里额外的一栏：厨友自己上传的作品 */
    public const USER_CATEGORY = '厨友作品';

    /** 可选菜系分类（上传作品时用） */
    public const CATEGORIES = [
        '家常菜', '川菜', '江浙菜', '粤菜', '湘菜', '汤羹', '海鲜', '炖菜', '面食', '烘焙', '轻食', '小吃',
    ];

    /** 难度档位 */
    public const DIFFICULTIES = ['简单', '中等', '有点难'];

    protected $fillable = [
        'user_id', 'title', 'category', 'cover', 'intro', 'prep_minutes', 'cook_minutes',
        'difficulty', 'servings', 'calories', 'author',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 仅厨友上传的作品 */
    public function scopeWorks(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    /** 是否用户上传的作品 */
    public function isWork(): bool
    {
        return $this->user_id !== null;
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('sort');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(Step::class)->orderBy('step_no');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class)->withPivot('price');
    }

    /** 准备 + 烹饪，总耗时 */
    public function getTotalMinutesAttribute(): int
    {
        return $this->prep_minutes + $this->cook_minutes;
    }

    /** 评论平均分，没有评论时返回 null */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->comments()->avg('rating');

        return $avg === null ? null : round((float) $avg, 1);
    }
}
