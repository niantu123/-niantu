<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantMessage extends Model
{
    protected $fillable = [
        'user_id', 'session_key', 'role', 'content', 'cards', 'quick', 'intent', 'source',
    ];

    protected function casts(): array
    {
        return [
            'cards' => 'array',
            'quick' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 是否助手的回答 */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
