@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase_create.css') }}">
@endsection

@section('content')

@php
$isKonbiniUnavailable = (int) $item->price > 300000;
@endphp

<main>
    <table class="purchase">
        <tr>
            <td>
                <table class="purchase__inner">
                    <tr>
                        {{-- 左カラム --}}
                        <td class="purchase-left">

                            {{-- 商品情報:商品画像/商品名/商品価格 --}}
                            <table class="purchase-item">
                                <tr>
                                    <td class="purchase-item__img">
                                        @if ($item->images->isNotEmpty())
                                        <img
                                            src="{{ asset('storage/' . $item->images->first()->image_path) }}"
                                            alt="{{ $item->name }}"
                                            class="purchase-item__img-tag">
                                        @else
                                        <div class="purchase-item__img-placeholder">商品画像</div>
                                        @endif
                                    </td>

                                    <td class="purchase-item__meta" valign="top">
                                        <div class="purchase-item__name">{{ $item->name }}</div>
                                        <div class="purchase-item__price">
                                            <div class="purchase-item__yen">¥</div>
                                            {{ number_format($item->price) }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div class="purchase-line"></div>

                            {{-- 支払い方法 --}}
                            <div class="purchase-section">
                                <div class="purchase-section__title">支払い方法</div>

                                <form method="GET" action="{{ route('purchase.payment', ['item_id' => $item->id]) }}">
                                    <select name="payment_method" onchange="this.form.submit()" class="purchase-select">
                                        @if (empty($payment_method))
                                        <option value="" selected disabled>選択してください</option>
                                        @endif

                                        <option
                                            value="1"
                                            {{ (int)$payment_method === 1 ? 'selected' : '' }}
                                            {{ $isKonbiniUnavailable ? 'disabled' : '' }}>
                                            コンビニ払い
                                        </option>

                                        <option value="2" {{ (int)$payment_method === 2 ? 'selected' : '' }}>
                                            カード支払い
                                        </option>
                                    </select>
                                </form>

                                @if($isKonbiniUnavailable)
                                <p class="form-error">30万円を超える商品はコンビニ決済を利用できません。</p>
                                @endif

                                @error('payment_method')
                                <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="purchase-line"></div>

                            {{-- 配送先 --}}
                            <table class="purchase-section">
                                <tr>
                                    <td>
                                        <div class="purchase-section__title">配送先</div>

                                        <div class="delivery-view">
                                            <div>〒 {{ $display_postcode }}</div>
                                            <div>{{ $display_address }}</div>
                                            @if(!empty($display_building))
                                            <div>{{ $display_building }}</div>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="delivery-action">
                                        <a href="{{ route('purchase.address.edit', ['item_id' => $item->id]) }}"
                                            class="delivery-change">
                                            変更する
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <div class="purchase-line"></div>

                        </td>

                        {{-- 右カラム --}}
                        <td class="purchase-right" valign="top">

                            <table class="summary-box" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="summary-label">商品代金</td>
                                    <td class="summary-value">¥{{ number_format($item->price) }}</td>
                                </tr>
                                <tr>
                                    <td class="summary-label">支払い方法</td>
                                    <td class="summary-value">{{ $payment_label }}</td>
                                </tr>
                            </table>

                            {{-- 購入エラー --}}
                            @if ($errors->has('purchase'))
                            <p class="form-error">{{ $errors->first('purchase') }}</p>
                            @endif

                            <form id="purchase-form" action="{{ route('purchase.store', ['item_id' => $item->id]) }}" method="POST">
                                @csrf

                                {{-- 支払い方法 --}}
                                <input type="hidden" name="payment_method" value="{{ old('payment_method', is_null($payment_method) ? '' : (int)$payment_method) }}">

                                {{-- 配送先 --}}
                                <input type="hidden" name="delivery_postcode" value="{{ old('delivery_postcode', $display_postcode) }}">
                                <input type="hidden" name="delivery_address" value="{{ old('delivery_address', $display_address) }}">
                                <input type="hidden" name="delivery_building" value="{{ old('delivery_building', $display_building) }}">

                                @if($item->isSold())
                                <button class="purchase-submit-btn is-disabled" disabled>
                                    売り切れました
                                </button>

                                @elseif($item->isCreditProcessingByOther(Auth::id()))
                                <button class="purchase-submit-btn is-disabled" disabled>
                                    ただいま決済処理中です
                                </button>

                                @elseif($isKonbiniUnavailable && (int)$payment_method === 1)
                                <button class="purchase-submit-btn is-disabled" disabled>
                                    30万円超のためコンビニ決済不可
                                </button>

                                @else
                                <button type="submit" class="purchase-submit-btn">購入する</button>
                                @endif
                            </form>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const form = document.getElementById('purchase-form');

        if (!form) return;

        form.addEventListener('submit', function() {

            const paymentMethod = form.querySelector('input[name="payment_method"]').value;

            if (paymentMethod === '1') {

                const stripeTab = window.open('', 'stripeTab');

                localStorage.setItem('stripe_tab_opened', '1');

            }

        });

    });
</script>

@endsection