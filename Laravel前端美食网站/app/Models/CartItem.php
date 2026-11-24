<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 购物车条目：一件食材商品 + 数量
 */
class CartItem extends Model
{
    protected $fillable = ['user_id', 'supplier_product_id', 'quantity'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class, 'supplier_product_id');
    }

    /** 这一行的小计金额 */
    public function getSubtotalAttribute(): float
    {
        return round(($this->product?->price ?? 0) * $this->quantity, 2);
    }
}
