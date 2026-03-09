<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeCheckoutController extends Controller
{
    public function checkout(int $item_id)
    {
        $item = Item::findOrFail($item_id);

        // セッションからCheckout用データを取得
        $checkoutData = [
            'item_id'           => session('checkout.item_id'),
            'user_id'           => session('checkout.user_id'),
            'payment_price'     => session('checkout.payment_price'),
            'payment_method'    => session('checkout.payment_method'),
            'delivery_postcode' => session('checkout.delivery_postcode'),
            'delivery_address'  => session('checkout.delivery_address'),
            'delivery_building' => session('checkout.delivery_building'),
        ];

        // セッションが欠けていたら購入画面へ戻す
        if (
            empty($checkoutData['item_id']) ||
            empty($checkoutData['user_id']) ||
            empty($checkoutData['payment_price']) ||
            empty($checkoutData['payment_method'])
        ) {
            return redirect()
                ->route('purchase.create', ['item_id' => $item_id])
                ->withErrors(['purchase' => '購入情報が不足しています。もう一度お試しください。']);
        }

        // セッション item_id と URL の item_id が一致しない場合は不正扱い
        if ((int) $checkoutData['item_id'] !== (int) $item->id) {
            abort(400, 'Invalid checkout target');
        }

        // 自分以外のチェックアウト情報は使えない
        if ((int) $checkoutData['user_id'] !== (int) Auth::id()) {
            abort(403);
        }

        $secret = config('services.stripe.secret');
        if (!$secret) {
            Log::error('STRIPE_SECRET is not set.');
            abort(500, 'Stripe secret is not set');
        }

        \Stripe\Stripe::setApiKey($secret);

        $amount = (int) $checkoutData['payment_price'];
        if ($amount <= 0) {
            Log::warning('Invalid payment_price for Stripe Checkout', [
                'item_id' => $item->id,
                'payment_price' => $checkoutData['payment_price'],
            ]);
            abort(400, 'Invalid payment amount');
        }

        $paymentMethod = (int) $checkoutData['payment_method'];
        $isCredit      = ($paymentMethod === Purchase::METHOD_CREDIT);
        $isConveni     = ($paymentMethod === Purchase::METHOD_CONVENI);

        if (!$isCredit && !$isConveni) {
            abort(400, 'Invalid payment method');
        }

        /**
         * すでに他人に確保 / 購入されていないか確認
         *
         * - PAID は常に不可
         * - コンビニ UNPAID は「自分の予約」なら許可
         * - 他人のコンビニ UNPAID は不可
         */
        $lockedPurchase = Purchase::where('item_id', $item->id)
            ->where(function ($q) {
                $q->where('payment_status', Purchase::STATUS_PAID)
                    ->orWhere(function ($sub) {
                        $sub->where('payment_method', Purchase::METHOD_CONVENI)
                            ->where('payment_status', Purchase::STATUS_UNPAID);
                    });
            })
            ->latest('id')
            ->first();

        if ($lockedPurchase) {
            $isOwnConveniReservation =
                (int) $lockedPurchase->user_id === (int) Auth::id()
                && (int) $lockedPurchase->payment_method === Purchase::METHOD_CONVENI
                && (int) $lockedPurchase->payment_status === Purchase::STATUS_UNPAID;

            if (!$isOwnConveniReservation) {
                return redirect()
                    ->route('items.show', ['item' => $item->id])
                    ->withErrors(['purchase' => 'この商品は既に購入されています。']);
            }
        }

        // 決済成功後は購入商品一覧へ
        $successUrl = route('mypage.show', ['page' => 'buy'], true);
        // キャンセル時
        $cancelUrl  = route('purchase.cancel', [], true);

        $itemName = (string) ($item->name ?? 'purchase');

        $params = [
            'mode' => 'payment',

            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => $itemName,
                    ],
                ],
            ]],

            // Webhook で purchase を新規作成 / 更新するための metadata
            'payment_intent_data' => [
                'metadata' => [
                    'item_id'           => (string) $item->id,
                    'user_id'           => (string) $checkoutData['user_id'],
                    'payment_method'    => (string) $paymentMethod,
                    'payment_price'     => (string) $amount,
                    'delivery_postcode' => (string) $checkoutData['delivery_postcode'],
                    'delivery_address'  => (string) $checkoutData['delivery_address'],
                    'delivery_building' => (string) ($checkoutData['delivery_building'] ?? ''),
                ],
            ],
            'metadata' => [
                'item_id'           => (string) $item->id,
                'user_id'           => (string) $checkoutData['user_id'],
                'payment_method'    => (string) $paymentMethod,
                'payment_price'     => (string) $amount,
                'delivery_postcode' => (string) $checkoutData['delivery_postcode'],
                'delivery_address'  => (string) $checkoutData['delivery_address'],
                'delivery_building' => (string) ($checkoutData['delivery_building'] ?? ''),
            ],

            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ];

        if ($isCredit) {
            $params['payment_method_types'] = ['card'];
        }

        if ($isConveni) {
            $params['payment_method_types'] = ['konbini'];
            $params['payment_method_options'] = [
                'konbini' => [
                    'expires_after_days' => Purchase::CONVENI_DUE_DAYS,
                ],
            ];
        }

        $session = \Stripe\Checkout\Session::create($params);

        return redirect()->away($session->url);
    }

    public function cancel()
    {
        // キャンセル時は checkout用sessionだけ破棄
        session()->forget([
            'checkout.item_id',
            'checkout.user_id',
            'checkout.payment_price',
            'checkout.payment_method',
            'checkout.delivery_postcode',
            'checkout.delivery_address',
            'checkout.delivery_building',
        ]);

        if (Auth::check()) {
            return redirect()->route('mypage.show', ['page' => 'buy'])
                ->with('message', '決済をキャンセルしました。');
        }

        return redirect()->route('items.index')
            ->with('message', '決済をキャンセルしました。');
    }
}
