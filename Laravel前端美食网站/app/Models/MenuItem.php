<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['restaurant_id', 'name', 'category', 'price', 'intro', 'is_signature', 'sort'];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_signature' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
