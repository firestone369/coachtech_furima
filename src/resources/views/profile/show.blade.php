@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile_show.css') }}">
@endsection

@section('content')

<div class="mypage">
    <div class="mypage__inner">

        {{-- 上部ヘッダー:アイコン / 名前 / 編集 --}}
        <div class="mypage-header">
            <div class="mypage-header__left">
                <div class="mypage-userIcon">
                    @if(Auth::user()->profile && Auth::user()->profile->icon_path)
                    <img
                        src="{{ Auth::user()->profile->icon_url }}"
                        alt="プロフィール画像"
                        class="mypage-userIcon__img">
                    @endif
                </div>
            </div>

            <div class="mypage-header__center">
                <div class="mypage-username">
                    {{ $user->name ?? Auth::user()->name ?? 'ユーザー名' }}
                </div>
            </div>

            <div class="mypage-header__right">
                <a href="{{ route('mypage.profile') }}" class="mypage-edit-btn">
                    プロフィールを編集
                </a>
            </div>
        </div>

        {{-- タブ:出品した商品/購入した商品 --}}
        <div class="mypage-tabs">
            <a href="{{ route('mypage.show', ['page' => 'sell']) }}"
                class="mypage-tabs__link {{ ($page ?? 'sell') === 'sell' ? 'is-active' : '' }}">
                出品した商品
            </a>

            <a href="{{ route('mypage.show', ['page' => 'buy']) }}"
                class="mypage-tabs__link {{ ($page ?? 'sell') === 'buy' ? 'is-active' : '' }}">
                購入した商品
            </a>
        </div>

        {{-- 一覧（sell / buy どちらも Controller が $items を作って渡す） --}}
        @php
        $list = $items ?? collect();
        @endphp

        @if($list->count())
        <div class="mypage-grid">
            @foreach($list as $item)
            <a href="{{ route('items.show', ['item' => $item->id]) }}" class="item-card">
                <div class="mypage-card__img">
                    @php $img = optional($item->images->first())->image_path; @endphp
                    @if($img)
                    <img
                        src="{{ asset('storage/' . $img) }}"
                        alt="{{ $item->name }}"
                        class="mypage-card__img-tag">
                    @endif

                    @if($item->isSold())
                    <div class="mypage-card__sold">SOLD</div>
                    @endif
                </div>

                <div class="mypage-card__name">
                    {{ $item->name }}
                </div>
            </a>
            @endforeach
        </div>
        @endif

    </div>
</div>

@if (session('open_stripe_url'))
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const stripeUrl = @json(session('open_stripe_url'));

        if (localStorage.getItem('stripe_tab_opened') === '1') {

            const stripeTab = window.open('', 'stripeTab');

            if (stripeTab) {
                stripeTab.location = stripeUrl;
            }

            localStorage.removeItem('stripe_tab_opened');
        }

    });
</script>
@endif

@endsection