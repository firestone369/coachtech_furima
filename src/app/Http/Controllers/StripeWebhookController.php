<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $secretKey = config('services.stripe.secret');
            $whSecret  = config('services.stripe.webhook_secret');

            if (!$secretKey) {
                Log::error('STRIPE_SECRET is not set.');
                return response('Stripe secret not set', 500);
            }

            if (!$whSecret) {
                Log::error('STRIPE_WEBHOOK_SECRET is not set.');
                return response('Webhook secret not set', 500);
            }

            \Stripe\Stripe::setApiKey($secretKey);

            $payload   = $request->getContent();
            $sigHeader = $request->header('Stripe-Signature');

            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $whSecret);
            } catch (\UnexpectedValueException $e) {
                Log::warning('Invalid Stripe payload', [
                    'error' => $e->getMessage(),
                ]);
                return response('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                Log::warning('Invalid Stripe signature', [
                    'error' => $e->getMessage(),
                ]);
                return response('Invalid signature', 400);
            }

            Log::info('Stripe webhook verified', [
                'type' => $event->type,
                'id'   => $event->id,
            ]);

            switch ($event->type) {
                case 'checkout.session.completed': {
                        /** @var \Stripe\Checkout\Session $session */
                        $session = $event->data->object;

                        $piId = $session->payment_intent ?? null;
                        $pi = null;

                        if ($piId) {
                            try {
                                $pi = \Stripe\PaymentIntent::retrieve($piId);
                            } catch (\Throwable $e) {
                                Log::warning('Failed to retrieve PaymentIntent', [
                                    'payment_intent_id' => $piId,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // ここで初めて purchase を作成する
                        $purchase = $this->findOrCreatePurchaseFromMetadata(
                            $session->metadata ?? null,
                            $pi?->metadata ?? null
                        );

                        if (!$purchase) {
                            Log::warning('Purchase creation failed in checkout.session.completed', [
                                'session_id' => $session->id ?? null,
                            ]);
                            break;
                        }

                        // メール送信は廃止
                        break;
                    }

                case 'payment_intent.succeeded': {
                        /** @var \Stripe\PaymentIntent $pi */
                        $pi = $event->data->object;

                        // payment_intent.succeeded が先に来ても purchase を作成/取得できるようにする
                        $purchase = $this->findOrCreatePurchaseFromMetadata($pi->metadata ?? null, null);

                        if (!$purchase) {
                            Log::warning('Purchase not found or created on payment_intent.succeeded', [
                                'payment_intent_id' => $pi->id,
                            ]);
                            break;
                        }

                        DB::transaction(function () use ($purchase) {
                            $purchase = Purchase::lockForUpdate()->find($purchase->id);

                            if (!$purchase) {
                                return;
                            }

                            if ((int) $purchase->payment_status === Purchase::STATUS_PAID) {
                                return;
                            }

                            // カードはここで支払い完了
                            // コンビニは実入金完了時にここが来たら PAID になる
                            $purchase->payment_status        = Purchase::STATUS_PAID;
                            $purchase->payment_complete_date = now()->toDateString();
                            $purchase->save();
                        });

                        break;
                    }

                default:
                    break;
            }

            return response('ok', Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook fatal error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response('Webhook internal error', 500);
        }
    }

    /**
     * metadata から purchase を取得、無ければ作成する
     */
    private function findOrCreatePurchaseFromMetadata($primaryMeta, $secondaryMeta = null): ?Purchase
    {
        $itemId           = $primaryMeta?->item_id ?? $secondaryMeta?->item_id ?? null;
        $userId           = $primaryMeta?->user_id ?? $secondaryMeta?->user_id ?? null;
        $paymentMethod    = $primaryMeta?->payment_method ?? $secondaryMeta?->payment_method ?? null;
        $paymentPrice     = $primaryMeta?->payment_price ?? $secondaryMeta?->payment_price ?? null;
        $deliveryPostcode = $primaryMeta?->delivery_postcode ?? $secondaryMeta?->delivery_postcode ?? null;
        $deliveryAddress  = $primaryMeta?->delivery_address ?? $secondaryMeta?->delivery_address ?? null;
        $deliveryBuilding = $primaryMeta?->delivery_building ?? $secondaryMeta?->delivery_building ?? null;

        if (
            !$itemId ||
            !$userId ||
            !$paymentMethod ||
            !$paymentPrice ||
            !$deliveryPostcode ||
            !$deliveryAddress
        ) {
            Log::warning('Required metadata missing', [
                'item_id'           => $itemId,
                'user_id'           => $userId,
                'payment_method'    => $paymentMethod,
                'payment_price'     => $paymentPrice,
                'delivery_postcode' => $deliveryPostcode,
                'delivery_address'  => $deliveryAddress,
                'delivery_building' => $deliveryBuilding,
            ]);

            return null;
        }

        return DB::transaction(function () use (
            $itemId,
            $userId,
            $paymentMethod,
            $paymentPrice,
            $deliveryPostcode,
            $deliveryAddress,
            $deliveryBuilding
        ) {
            DB::table('items')
                ->where('id', (int) $itemId)
                ->lockForUpdate()
                ->first();

            $existing = Purchase::where('item_id', (int) $itemId)
                ->where('user_id', (int) $userId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            return Purchase::create([
                'user_id'               => (int) $userId,
                'item_id'               => (int) $itemId,
                'payment_price'         => (int) $paymentPrice,
                'payment_method'        => (int) $paymentMethod,
                // checkout.session.completed 到達時点では、いったん未払いで作成
                // カードは payment_intent.succeeded で PAID へ更新
                // コンビニは支払い予約状態（UNPAID）のまま保持
                'payment_status'        => Purchase::STATUS_UNPAID,
                'payment_due_date'      => ((int) $paymentMethod === Purchase::METHOD_CONVENI)
                    ? now()->addDays(Purchase::CONVENI_DUE_DAYS)->toDateString()
                    : null,
                'payment_complete_date' => null,
                'delivery_postcode'     => (string) $deliveryPostcode,
                'delivery_address'      => (string) $deliveryAddress,
                'delivery_building'     => (string) ($deliveryBuilding ?? ''),
            ]);
        });
    }
}
