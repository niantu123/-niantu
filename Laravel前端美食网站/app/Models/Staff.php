<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    protected $table = 'restaurant_staff';

    public $timestamps = false;

    protected $fillable = ['restaurant_id', 'name', 'role', 'title', 'bio', 'years', 'sort'];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** 角色中文名 */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'chef' => '主厨',
            'waiter' => '服务',
            'manager' => '店长',
            default => '成员',
        };
    }
}
