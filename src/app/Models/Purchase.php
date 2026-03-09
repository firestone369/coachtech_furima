<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Item;
use App\Models\User;

class Purchase extends Model
{
    use HasFactory;

    // 支払方法
    public const METHOD_CONVENI = 1;
    public const METHOD_CREDIT  = 2;

    // 支払状態
    public const STATUS_PAID    = 1; // 支払い完了
    public const STATUS_UNPAID  = 2; // 未決済
    public const STATUS_EXPIRED = 3; // 期限切れキャンセル

    // コンビニ支払期限（日数）
    public const CONVENI_DUE_DAYS = 7;

    protected $fillable = [
        'user_id',
        'item_id',
        'payment_price',
        'payment_method',
        'payment_status',
        'payment_due_date',
        'payment_complete_date',
        'delivery_postcode',
        'delivery_address',
        'delivery_building',
    ];

    protected $casts = [
        'payment_due_date'      => 'date',
        'payment_complete_date' => 'date',
    ];

    public function isPaid(): bool
    {
        return (int)$this->payment_status === self::STATUS_PAID;
    }

    public function isUnpaid(): bool
    {
        return (int)$this->payment_status === self::STATUS_UNPAID;
    }

    public function isExpired(): bool
    {
        return (int)$this->payment_status === self::STATUS_EXPIRED;
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
