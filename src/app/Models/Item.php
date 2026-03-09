<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Category;
use App\Models\ItemImage;
use App\Models\Purchase;

class Item extends Model
{
    use HasFactory;

    // 販売状態
    public const STATUS_ON_SALE = 1;
    public const STATUS_SOLD    = 2;

    // 商品状態
    public const CONDITION_GOOD = 1;
    public const CONDITION_NO_VISIBLE_DAMAGE = 2;
    public const CONDITION_SOME_DAMAGE = 3;
    public const CONDITION_BAD = 4;

    public const CONDITION_LABELS = [
        self::CONDITION_GOOD => '良好',
        self::CONDITION_NO_VISIBLE_DAMAGE => '目立った傷や汚れなし',
        self::CONDITION_SOME_DAMAGE => 'やや傷や汚れあり',
        self::CONDITION_BAD => '状態が悪い',
    ];

    protected $fillable = [
        'user_id',
        'condition',
        'name',
        'brand',
        'description',
        'price',
        'sale_status',
    ];

    /**
     * 購入不可（SOLD扱い）判定
     *
     * - 支払完了済み（PAID）は常にSOLD
     * - コンビニ払いは checkout.session.completed 到達時点で購入成立とみなし、
     *   未入金（UNPAID）でもSOLD
     * - クレジット払いは未決済（UNPAID）の間はSOLDにしない
     */
    public function isSold(): bool
    {
        $this->ensurePurchaseLoaded();

        if (!$this->purchase) {
            return false;
        }

        // 支払い完了は常にSOLD
        if (
            (int) $this->purchase->payment_status === Purchase::STATUS_PAID
            && !is_null($this->purchase->payment_complete_date)
        ) {
            return true;
        }

        // コンビニ未入金は購入成立済みとしてSOLD
        $isConveni = (int) $this->purchase->payment_method === Purchase::METHOD_CONVENI;
        $isUnpaid  = (int) $this->purchase->payment_status === Purchase::STATUS_UNPAID;

        return $isConveni && $isUnpaid;
    }

    /**
     * クレジット決済中かどうか
     * - クレカで purchase は作成済みだが、まだ決済完了していない状態
     */
    public function isCreditProcessing(): bool
    {
        $this->ensurePurchaseLoaded();

        if (!$this->purchase) {
            return false;
        }

        return (int) $this->purchase->payment_method === Purchase::METHOD_CREDIT
            && (int) $this->purchase->payment_status === Purchase::STATUS_UNPAID;
    }

    /**
     * クレカ決済中だが「自分の決済ではない」(= 他人が処理中) 判定
     */
    public function isCreditProcessingByOther(?int $userId): bool
    {
        if (!$this->isCreditProcessing()) {
            return false;
        }

        if ($userId === null) {
            return false;
        }

        return (int) $this->purchase->user_id !== (int) $userId;
    }

    private function ensurePurchaseLoaded(): void
    {
        if (!$this->relationLoaded('purchase')) {
            $this->load('purchase');
        }
    }

    public function scopeKeywordSearch($query, $keyword)
    {
        return $query->when($keyword, function ($q) use ($keyword) {
            $q->where('name', 'like', '%' . $keyword . '%');
        });
    }

    public function scopeExcludeMyItems($query)
    {
        return $query->when(Auth::check(), function ($q) {
            $q->where('user_id', '!=', Auth::id());
        });
    }

    public function getConditionLabelAttribute()
    {
        return self::CONDITION_LABELS[$this->condition] ?? '未設定';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likedUsers()
    {
        return $this->belongsToMany(User::class, 'likes');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'item_categories');
    }

    public function images()
    {
        return $this->hasMany(ItemImage::class);
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }
}
