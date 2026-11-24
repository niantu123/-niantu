<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 到店预约：某个用户在某家餐厅订了哪天的哪个时段、几个人
 */
class Booking extends Model
{
    /** 待商家确认 */
    public const STATUS_PENDING = 'pending';

    /** 商家已确认 */
    public const STATUS_CONFIRMED = 'confirmed';

    /** 用户已取消 */
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => '待商家确认',
        self::STATUS_CONFIRMED => '已确认',
        self::STATUS_CANCELLED => '已取消',
    ];

    /** 可预约的时段（半小时一档，午市 + 晚市） */
    public const TIME_SLOTS = [
        '11:00', '11:30', '12:00', '12:30', '13:00', '13:30',
        '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30',
    ];

    /** 单次预约最多几人 */
    public const MAX_PARTY = 12;

    /** 最多提前几天预约 */
    public const DAYS_AHEAD = 30;

    protected $fillable = [
        'user_id', 'restaurant_id', 'book_date', 'book_time', 'party_size',
        'contact_name', 'contact_phone', 'note', 'status',
    ];

    protected function casts(): array
    {
        return [
            'book_date' => 'date',
            'party_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** 预约号：SG + 年月日 + 五位序号，界面上给用户和店家对账用 */
    public function getNumberAttribute(): string
    {
        return 'SG'.$this->created_at?->format('ymd').str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? '待商家确认';
    }

    /** 是否还能取消（还没到店、也没被取消） */
    public function getCancellableAttribute(): bool
    {
        return $this->status !== self::STATUS_CANCELLED
            && $this->book_date
            && $this->book_date->endOfDay()->isFuture();
    }
}
