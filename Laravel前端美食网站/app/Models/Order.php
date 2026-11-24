<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 食材订单：结算时生成的快照，购物车清空后仍能看到买了什么
 */
class Order extends Model
{
    public const STATUS_LABELS = [
        'pending' => '待发货',
        'done' => '已完成',
        'cancelled' => '已取消',
    ];

    protected $fillable = [
        'user_id', 'order_no', 'total', 'item_count',
        'contact_name', 'contact_phone', 'address', 'note', 'status', 'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'total' => 'float',
            'item_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '待发货';
    }
}
