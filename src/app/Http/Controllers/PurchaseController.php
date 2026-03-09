<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    /* 購入確認画面
     * - デフォルトはプロフィールの住所を表示。住所・支払方法はセッション優先。
     */
    public function create($item_id)
    {
        $item_id = (int) $item_id;

        // 購入フロー内の戻り（住所変更・支払変更）以外は初期化
        if (!request()->boolean('keep')) {
            session()->forget([
                'purchase.payment_method',
                'purchase.delivery_postcode',
                'purchase.delivery_address',
                'purchase.delivery_building',
            ]);
        }

        $item = $this->getItemWithImages($item_id);

        $delivery = $this->getDeliveryFromSessionOrProfile();

        // 支払い方法：セッション優先（未選択はnull）
        $payment_method = old('payment_method', session('purchase.payment_method'));

        if (!in_array((int) $payment_method, [Purchase::METHOD_CONVENI, Purchase::METHOD_CREDIT], true)) {
            $payment_method = null;
        }

        $payment_label = is_null($payment_method)
            ? '選択してください'
            : ((int) $payment_method === Purchase::METHOD_CONVENI ? 'コンビニ払い' : 'カード支払い');

        return view('purchase.create', [
            'item'             => $item,
            'payment_method'   => $payment_method,
            'payment_label'    => $payment_label,
            'display_postcode' => $delivery['postcode'],
            'display_address'  => $delivery['address'],
            'display_building' => $delivery['building'],
        ]);
    }

    /* 配送先住所変更画面 */
    public function editAddress($item_id)
    {
        $item_id = (int) $item_id;

        $item = $this->getItemWithImages($item_id);

        $delivery = $this->getDeliveryFromSessionOrProfile();

        return view('purchase.address', [
            'item'             => $item,
            'initial_postcode' => $delivery['postcode'],
            'initial_address'  => $delivery['address'],
            'initial_building' => $delivery['building'],
        ]);
    }

    /* 配送先住所をセッションに保存 */
    public function updateAddress(AddressRequest $request, $item_id)
    {
        $item_id = (int) $item_id;

        session([
            'purchase.delivery_postcode' => $request->delivery_postcode,
            'purchase.delivery_address'  => $request->delivery_address,
            'purchase.delivery_building' => $request->delivery_building,
        ]);

        return redirect()->route('purchase.create', ['item_id' => $item_id, 'keep' => 1]);
    }

    /* 支払方法をセッションに保存 */
    public function setPayment(Request $request, $item_id)
    {
        $item_id = (int) $item_id;

        $validated = $request->validate([
            'payment_method' => [
                'required',
                'integer',
                'in:' . Purchase::METHOD_CREDIT . ',' . Purchase::METHOD_CONVENI,
            ],
        ]);

        session([
            'purchase.payment_method' => (int) $validated['payment_method'],
        ]);

        return redirect()->route('purchase.create', ['item_id' => $item_id, 'keep' => 1]);
    }

    /* 購入開始 */
    public function store(PurchaseRequest $request, $item_id)
    {
        $item_id = (int) $item_id;

        $item = Item::findOrFail($item_id);

        // 出品者は自分の商品を購入できない
        if ($this->isOwnItem($item)) {
            return back()->withErrors(['purchase' => '自分が出品した商品は購入できません。']);
        }

        $paymentMethod = (int) $request->payment_method;
        $isConveni     = ($paymentMethod === Purchase::METHOD_CONVENI);
        $isCredit      = ($paymentMethod === Purchase::METHOD_CREDIT);

        if (!$isConveni && !$isCredit) {
            return back()->withErrors(['purchase' => '支払方法が不正です。']);
        }

        // コンビニ決済は30万円以下のみ
        if ($isConveni && (int) $item->price > 300000) {
            return back()->withErrors([
                'purchase' => '30万円を超える商品はコンビニ決済を利用できません。'
            ]);
        }

        // すでに購入確定済み / コンビニ予約済みなら購入不可
        $existingLockedPurchase = Purchase::where('item_id', $item->id)
            ->where(function ($q) {
                $q->where('payment_status', Purchase::STATUS_PAID)
                    ->orWhere(function ($sub) {
                        $sub->where('payment_method', Purchase::METHOD_CONVENI)
                            ->where('payment_status', Purchase::STATUS_UNPAID);
                    });
            })
            ->exists();

        if ($existingLockedPurchase) {
            return back()->withErrors(['purchase' => 'この商品は既に購入されています。']);
        }

        /**
         * コンビニ払い:
         * - ここで purchase を即作成
         * - 現在タブは購入一覧へ戻す
         * - Stripe は別タブで開く
         */
        if ($isConveni) {
            Purchase::create([
                'user_id'               => Auth::id(),
                'item_id'               => $item->id,
                'payment_price'         => $item->price,
                'payment_method'        => Purchase::METHOD_CONVENI,
                'payment_status'        => Purchase::STATUS_UNPAID,
                'payment_due_date'      => now()->addDays(Purchase::CONVENI_DUE_DAYS)->toDateString(),
                'payment_complete_date' => null,
                'delivery_postcode'     => $request->delivery_postcode,
                'delivery_address'      => $request->delivery_address,
                'delivery_building'     => $request->delivery_building,
            ]);

            // Stripe Checkout 用 session
            session([
                'checkout.item_id'            => $item->id,
                'checkout.user_id'            => Auth::id(),
                'checkout.payment_price'      => $item->price,
                'checkout.payment_method'     => $paymentMethod,
                'checkout.delivery_postcode'  => $request->delivery_postcode,
                'checkout.delivery_address'   => $request->delivery_address,
                'checkout.delivery_building'  => $request->delivery_building,
            ]);

            // 購入入力用 session はクリア
            session()->forget([
                'purchase.payment_method',
                'purchase.delivery_postcode',
                'purchase.delivery_address',
                'purchase.delivery_building',
            ]);

            // flash session で 1 回だけ Stripe 別タブ起動URLを渡す
            return redirect()
                ->route('mypage.show', ['page' => 'buy'])
                ->with('open_stripe_url', route('stripe.checkout', ['item_id' => $item->id]));
        }

        /**
         * カード払い:
         * - これまで通り purchase は作らない
         * - Checkout用 session だけ保存して Stripe へ
         */
        session([
            'checkout.item_id'            => $item->id,
            'checkout.user_id'            => Auth::id(),
            'checkout.payment_price'      => $item->price,
            'checkout.payment_method'     => $paymentMethod,
            'checkout.delivery_postcode'  => $request->delivery_postcode,
            'checkout.delivery_address'   => $request->delivery_address,
            'checkout.delivery_building'  => $request->delivery_building,
        ]);

        session()->forget([
            'purchase.payment_method',
            'purchase.delivery_postcode',
            'purchase.delivery_address',
            'purchase.delivery_building',
        ]);

        return redirect()->route('stripe.checkout', [
            'item_id' => $item->id,
        ]);
    }

    /**
     * -------------------------
     * private helpers
     * -------------------------
     */
    private function getItemWithImages(int $itemId): Item
    {
        return Item::with(['images', 'purchase'])->findOrFail($itemId);
    }

    /**
     * セッション優先、なければプロフィールを使用
     */
    private function getDeliveryFromSessionOrProfile(): array
    {
        $profile = Auth::user()?->profile;

        return [
            'postcode' => (string) session('purchase.delivery_postcode', $profile?->postcode ?? ''),
            'address'  => (string) session('purchase.delivery_address',  $profile?->address ?? ''),
            'building' => (string) session('purchase.delivery_building', $profile?->building ?? ''),
        ];
    }

    private function isOwnItem(Item $item): bool
    {
        return Auth::check() && (int) $item->user_id === (int) Auth::id();
    }
}
