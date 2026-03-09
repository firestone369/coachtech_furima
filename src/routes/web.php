<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MyPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\StripeCheckoutController;

/*
|--------------------------------------------------------------------------
| 誰でも閲覧可能（guest OK）
|--------------------------------------------------------------------------
*/

// 商品一覧（おすすめ）
Route::get('/', [ItemController::class, 'index'])
    ->name('items.index');

// 検索（ログイン時、未ログイン時両方有効）
Route::get('/items/search', [ItemController::class, 'search'])
    ->name('items.search');

// 商品詳細
Route::get('/items/{item}', [ItemController::class, 'show'])
    ->whereNumber('item')
    ->name('items.show');

// ★Stripeの戻り先は auth の外（ここが重要）
Route::get('/purchase/success', [StripeCheckoutController::class, 'success'])
    ->name('purchase.success');

Route::get('/purchase/cancel', [StripeCheckoutController::class, 'cancel'])
    ->name('purchase.cancel');


/*
|--------------------------------------------------------------------------
| ログインユーザー専用（auth）
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // マイページ
    Route::get('/mypage', [MyPageController::class, 'show'])
        ->name('mypage.show');

    // 出品
    Route::get('/sell', [ItemController::class, 'create'])
        ->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])
        ->name('items.store');

    // コメント投稿
    Route::post('/items/{item}/comments', [ItemController::class, 'storeComment'])
        ->name('items.comments.store');

    // 購入
    Route::get('/purchase/{item_id}', [PurchaseController::class, 'create'])
        ->name('purchase.create');
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])
        ->name('purchase.store');

    // いいね
    Route::post('/items/{item}/likes', [ItemController::class, 'like'])
        ->name('items.likes.store');
    Route::delete('/items/{item}/likes', [ItemController::class, 'unlike'])
        ->name('items.likes.destroy');

    // 住所変更（購入ごと）
    Route::get('/purchase/address/{item_id}', [PurchaseController::class, 'editAddress'])
        ->name('purchase.address.edit');
    Route::patch('/purchase/address/{item_id}', [PurchaseController::class, 'updateAddress'])
        ->name('purchase.address.update');

    // 支払方法タブ切替
    Route::get('/purchase/{item_id}/payment', [PurchaseController::class, 'setPayment'])
        ->name('purchase.payment');

    // Stripe Checkout開始（ログイン必須でOK）
    Route::get('/stripe/checkout/{item_id}', [StripeCheckoutController::class, 'checkout'])
        ->name('stripe.checkout');

    /*
    |--------------------------------------------------------------------------
    | 認証済み（verified）専用
    |--------------------------------------------------------------------------
    */
    Route::middleware('verified')->group(function () {
        Route::get('/mypage/profile', [ProfileController::class, 'edit'])
            ->name('mypage.profile');
        Route::patch('/mypage/profile', [ProfileController::class, 'update'])
            ->name('profile.update');
    });
});
