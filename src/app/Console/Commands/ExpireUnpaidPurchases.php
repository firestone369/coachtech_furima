<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidPurchases extends Command
{
    /**
     * 実行コマンド:
     * php artisan purchases:expire-unpaid --minutes=30
     */
    protected $signature = 'purchases:expire-unpaid {--minutes=30 : クレカ未決済(UNPAID)を期限切れ(EXPIRED)にするまでの分数}';

    protected $description = '未決済購入(UNPAID)を期限切れ(EXPIRED)に更新する（クレカ=経過分 / コンビニ=支払期限日）';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        if ($minutes <= 0) {
            $this->error('--minutes は 1 以上を指定してください');
            return self::FAILURE;
        }

        $threshold = now()->subMinutes($minutes);
        $today     = now()->toDateString(); // YYYY-MM-DD（アプリTZ）

        /**
         * 1) クレカ：未決済が一定時間経過したら期限切れ
         */
        $creditTargets = Purchase::query()
            ->where('payment_method', Purchase::METHOD_CREDIT)
            ->where('payment_status', Purchase::STATUS_UNPAID)
            ->where('created_at', '<=', $threshold)
            ->orderBy('id')
            ->pluck('id');

        $creditExpired = 0;

        foreach ($creditTargets as $purchaseId) {
            DB::transaction(function () use ($purchaseId, &$creditExpired) {
                $purchase = Purchase::lockForUpdate()->find($purchaseId);

                if (!$purchase) {
                    return;
                }

                // 二重実行・他処理で更新済みならスキップ
                if ((int) $purchase->payment_status !== Purchase::STATUS_UNPAID) {
                    return;
                }
                if ((int) $purchase->payment_method !== Purchase::METHOD_CREDIT) {
                    return;
                }

                $purchase->payment_status        = Purchase::STATUS_EXPIRED;
                $purchase->payment_complete_date = null;
                $purchase->save();

                $creditExpired++;
            });
        }

        /**
         * コンビニ：支払期限日を過ぎた未決済は期限切れ（確保解除）
         */
        $conveniTargets = Purchase::query()
            ->where('payment_method', Purchase::METHOD_CONVENI)
            ->where('payment_status', Purchase::STATUS_UNPAID)
            ->whereNotNull('payment_due_date')
            ->where('payment_due_date', '<', $today)
            ->orderBy('id')
            ->pluck('id');

        $conveniExpired = 0;

        foreach ($conveniTargets as $purchaseId) {
            DB::transaction(function () use ($purchaseId, &$conveniExpired) {
                $purchase = Purchase::lockForUpdate()->find($purchaseId);

                if (!$purchase) {
                    return;
                }

                if ((int) $purchase->payment_status !== Purchase::STATUS_UNPAID) {
                    return;
                }
                if ((int) $purchase->payment_method !== Purchase::METHOD_CONVENI) {
                    return;
                }

                $purchase->payment_status        = Purchase::STATUS_EXPIRED;
                $purchase->payment_complete_date = null;
                $purchase->save();

                $conveniExpired++;
            });
        }

        $total = $creditExpired + $conveniExpired;

        $this->info("期限切れに更新: {$total}件（クレカ={$creditExpired} / コンビニ={$conveniExpired}）");

        Log::info('ExpireUnpaidPurchases executed', [
            'minutes' => $minutes,
            'credit_expired' => $creditExpired,
            'conveni_expired' => $conveniExpired,
            'total' => $total,
        ]);

        return self::SUCCESS;
    }
}
