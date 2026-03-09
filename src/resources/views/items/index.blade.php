@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items_index.css') }}">
@endsection

@section('content')

<div class="items-page">
    <div class="items-page__inner">

        {{-- おすすめ/マイリストのタブ（未ログイン時はルーティングで弾く） --}}
        @php
        // tabのデフォルトは recommend に寄せる
        $tab = request('tab', 'recommend');
        $keyword = request('keyword');
        @endphp

        <nav class="items-tabs" aria-label="商品一覧タブ">
            {{-- おすすめ：検索keywordを保持したまま recommend へ --}}
            <a href="{{ route('items.index', array_filter(['tab' => 'recommend', 'keyword' => $keyword])) }}"
                class="items-tabs__link {{ $tab !== 'mylist' ? 'is-active' : '' }}">
                おすすめ
            </a>

            {{-- マイリスト：ログイン時は検索keywordを保持したまま mylist へ --}}
            @auth
            <a href="{{ route('items.index', array_filter(['tab' => 'mylist', 'keyword' => $keyword])) }}"
                class="items-tabs__link {{ $tab === 'mylist' ? 'is-active' : '' }}">
                マイリスト
            </a>
            @else
            <a href="{{ route('login') }}" class="items-tabs__link">マイリスト</a>
            @endauth
        </nav>


        {{-- 商品一覧 --}}
        @if(isset($items) && $items->count())
        <div class="items-grid">
            @foreach($items as $item)
            <a href="{{ route('items.show', ['item' => $item->id]) }}"
                class="item-card"
                role="listitem">
                <div class="item-card__img">
                    <img
                        src="{{ asset('storage/' . optional($item->images->first())->image_path) }}"
                        alt="{{ $item->name }}"
                        class="item-card__img-tag">
                    @if($item->isSold())
                    <div class="item-card__sold">SOLD</div>
                    @endif
                </div>

                <div class="item-card__name">
                    {{ $item->name }}
                </div>

            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

@endsection