<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierProduct extends Model
{
    public $timestamps = false;

    protected $fillable = ['supplier_id', 'name', 'category', 'price', 'unit', 'intro', 'is_signature', 'sort'];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_signature' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** 加购了这件商品的人 */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
