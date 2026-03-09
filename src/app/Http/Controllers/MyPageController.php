<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyPageController extends Controller
{
    public function show(Request $request)
    {
        // sell / buy 以外は sell に丸める
        $page = $request->query('page', 'sell');
        if (!in_array($page, ['sell', 'buy'], true)) {
            $page = 'sell';
        }

        $user = Auth::user()->load('profile');
        $profile = $user->profile;

        $query = Item::query()
            ->with(['images', 'purchase'])
            ->orderByDesc('created_at');

        if ($page === 'buy') {
            // 購入した商品
            // - 支払い完了済み（PAID）は表示
            // - コンビニ支払い予約中（UNPAID）も表示
            // - 期限切れ（EXPIRED）は表示しない
            $query->whereHas('purchase', function ($q) {
                $q->where('user_id', Auth::id())
                    ->where(function ($sub) {
                        $sub->where(function ($paid) {
                            $paid->where('payment_status', Purchase::STATUS_PAID)
                                ->whereNotNull('payment_complete_date');
                        })->orWhere(function ($conveniUnpaid) {
                            $conveniUnpaid->where('payment_method', Purchase::METHOD_CONVENI)
                                ->where('payment_status', Purchase::STATUS_UNPAID);
                        });
                    });
            });
        } else {
            // 出品した商品（items.user_id が自分）
            $query->where('user_id', Auth::id());
        }

        $items = $query->get();

        return view('profile.show', compact('user', 'profile', 'items', 'page'));
    }
}
