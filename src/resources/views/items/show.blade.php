@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items_show.css') }}">
@endsection

@section('content')

<main>

    <table class="item-show">
        <tr>

            <!-- 左側：商品画像表示 -->
            <td class="item-show__image">

                <div class="item-show__image-box">
                    @if ($item->images->isNotEmpty())
                    <img
                        src="{{ asset('storage/' . $item->images->first()->image_path) }}"
                        alt="{{ $item->name }}"
                        class="item-show__image-img">
                    @else
                    <div class="item-show__image-placeholder">No Image</div>
                    @endif
                </div>

            </td>

            <!-- 右側：商品情報 -->
            <td class="item-show__right">

                <table class="item-show__right-table">

                    <!-- 商品名/ブランド名 -->
                    <tr>
                        <td>
                            <h1>{{ $item->name ?? '' }}</h1>
                            <p>{{ $item->brand ?? '' }}</p>
                        </td>
                    </tr>

                    <!-- 価格 -->
                    <tr>
                        <td>
                            <strong class="item-price">
                                <span class="item-price__symbol">¥</span>
                                {{ number_format($item->price ?? 0) }}
                                <span class="item-price__tax">（税込）</span>
                            </strong>
                        </td>
                    </tr>

                    <!-- いいね/コメントアイコン -->
                    <tr>
                        <td>
                            <table class="item-icons-table">
                                <tr>
                                    {{-- いいね --}}
                                    <td class="icon-cell">
                                        @auth
                                        @if($isLiked)
                                        <form method="POST" action="{{ route('items.likes.destroy', ['item' => $item->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="icon-btn" aria-label="いいね解除">
                                                <img src="{{ asset('images/heart_active.png') }}" alt="">
                                            </button>
                                        </form>
                                        @else
                                        <form method="POST" action="{{ route('items.likes.store', ['item' => $item->id]) }}">
                                            @csrf
                                            <button type="submit" class="icon-btn" aria-label="いいね">
                                                <img src="{{ asset('images/heart_default.png') }}" alt="">
                                            </button>
                                        </form>
                                        @endif
                                        @else
                                        <a href="{{ route('login') }}" class="icon-btn" aria-label="ログインしていいね">
                                            <img src="{{ asset('images/heart_default.png') }}" alt="">
                                        </a>
                                        @endauth
                                    </td>

                                    {{-- コメント --}}
                                    <td class="icon-cell">
                                        <img src="{{ asset('images/comment_icon.png') }}" alt="コメント" class="icon-img">
                                    </td>
                                </tr>

                                <tr>
                                    {{-- いいね数 --}}
                                    <td class="count-cell">
                                        {{ $item->likes_count ?? 0 }}
                                    </td>

                                    {{-- コメント数 --}}
                                    <td class="count-cell">
                                        {{ $item->comments_count ?? 0 }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- 購入ボタン --}}
                    <tr>
                        <td>
                            @if($item->isSold())
                            <button class="purchase-btn is-disabled" disabled>売り切れ</button>

                            @elseif($item->isCreditProcessingByOther(Auth::id()))
                            <button class="purchase-btn is-disabled" disabled>決済処理中</button>

                            @elseif(Auth::check() && $item->user_id === Auth::id())
                            <button class="purchase-btn is-disabled" disabled>自分が出品した商品につき、購入不可</button>

                            @elseif(Auth::check())
                            <a href="{{ route('purchase.create', ['item_id' => $item->id]) }}" class="purchase-btn">購入手続きへ</a>

                            @else
                            <a href="{{ route('login') }}" class="purchase-btn">購入手続きへ</a>
                            @endif
                        </td>
                    </tr>

                    <!-- 商品説明 -->
                    <tr>
                        <td>
                            <h2>商品説明</h2>
                            <p class="marketing-copy-wrap">{!! nl2br(e($item->description ?? '')) !!}</p>
                        </td>
                    </tr>

                    <!-- 商品の情報：カテゴリー/商品の状態 -->
                    <tr>
                        <td>
                            <h2>商品の情報</h2>

                            <div class="item-info-box">
                                <table class="item-info-table">
                                    <tr>
                                        <th class="item-info-label">カテゴリー</th>
                                        <td class="item-info-value">
                                            <div class="pill-wrap">
                                                @foreach ($item->categories ?? [] as $category)
                                                <span class="pill">{{ $category->name }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th class="item-info-label">商品の状態</th>
                                        <td class="item-info-value">
                                            {{ $item->condition_label }}
                                        </td>
                                    </tr>
                                </table>
                            </div>

                        </td>
                    </tr>

                    {{-- コメント表示 --}}
                    <tr>
                        <td>
                            <h2>コメント（{{ $item->comments_count ?? 0 }}）</h2>

                            @if ($item->comments->isNotEmpty())
                            <div class="comments">
                                @foreach ($item->comments as $comment)
                                @php
                                $user = $comment->user;
                                $profile = $user?->profile;
                                $displayName = $user?->name ?? 'No Name';
                                @endphp

                                <div class="comment-item">
                                    <div class="comment-header">
                                        <div class="mypage-userIcon">
                                            <img
                                                src="{{ $profile?->icon_url ?? asset('images/default_icon.png') }}"
                                                class="mypage-userIcon__img"
                                                alt="">
                                        </div>

                                        <div class="comment-name">
                                            {{ $displayName }}
                                        </div>
                                    </div>

                                    <div class="comment-body">
                                        {!! nl2br(e($comment->body ?? '')) !!}
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </td>
                    </tr>


                    {{-- 投稿フォーム --}}
                    <tr>
                        <td>
                            @auth
                            <form action="{{ route('items.comments.store', ['item' => $item->id]) }}" method="POST">
                                @csrf

                                <table class="comment-form-table">
                                    <tr>
                                        <td>
                                            <h2 class="comment-form-title">商品へのコメント</h2>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <textarea name="comment" class="comment-form-textarea">{{ old('comment') }}</textarea>

                                            @error('comment')
                                            <p class="form-error">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <button type="submit" class="comment-form-button">コメントを送信する</button>
                                        </td>
                                    </tr>
                                </table>
                            </form>
                            @else
                            <form action="{{ route('login') }}" method="GET">
                                <table class="comment-form-table">
                                    <tr>
                                        <td>
                                            <h2 class="comment-form-title">商品へのコメント</h2>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><textarea class="comment-form-textarea" disabled></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><button type="submit" class="comment-form-button">コメントを送信する</button></td>
                                    </tr>
                                </table>
                            </form>
                            @endauth

                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</main>
@endsection