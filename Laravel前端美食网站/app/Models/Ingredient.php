<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends Model
{
    protected $table = 'recipe_ingredients';

    public $timestamps = false;

    protected $fillable = ['recipe_id', 'name', 'amount', 'sort'];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
