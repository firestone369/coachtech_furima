<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtechフリマ</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>

    @php
    $isAuthPage = $isAuthPage ?? false;
    @endphp

    <table class="site-header">
        <tr>
            <td class="site-header__inner">
                <div class="site-header__left">
                    <div class="site-header__logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('images/COACHTECH_header_logo.png') }}" alt="coachtechフリマロゴ">
                        </a>
                    </div>
                </div>

                <!-- 画面上部中央の検索ボックス（ログイン・新規登録では非表示） -->

                @php
                $currentTab = request('tab','recommend');
                @endphp

                <div class="site-header__center">
                    <form action="{{ route('items.search') }}" method="get" class="header-search">
                        <input type="hidden" name="tab" value="{{ $currentTab === 'mylist' ? 'mylist' : 'recommend' }}">

                        <input
                            type="text"
                            name="keyword"
                            class="header-search__input"
                            placeholder="なにをお探しですか？"
                            value="{{ request('keyword') }}">
                    </form>
                </div>

                <!-- ログイン：画面上部黒帯の右端のログアウト/マイページ/出品 -->
                <div class="site-header__right">

                    @auth
                    <table class="header-nav" role="presentation">
                        <tr>
                            <td class="header-nav__item">
                                <a href="{{ route('login') }}"
                                    class="header-nav__link"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    ログアウト
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="post" class="is-hidden">
                                    @csrf
                                </form>
                            </td>

                            <td class="header-nav__item">
                                <a href="{{ route('mypage.show') }}" class="header-nav__link">マイページ</a>
                            </td>

                            <td class="header-nav__item">
                                <a href="{{ route('items.create') }}" class="header-nav__button">出品</a>
                            </td>
                        </tr>
                    </table>
                    @endauth

                    <!-- 未ログイン：画面上部黒帯の右端のログイン/マイページ/出品（未ログインなので、どれを押してもログイン画面に遷移する） -->
                    @guest
                    <table class="header-nav" role="presentation">
                        <tr>
                            <td class="header-nav__item">
                                <a href="{{ route('login') }}" class="header-nav__link">ログイン</a>
                            </td>

                            <td class="header-nav__item">
                                <a href="{{ route('login') }}" class="header-nav__link">マイページ</a>
                            </td>

                            <td class="header-nav__item">
                                <a href="{{ route('login') }}" class="header-nav__button">出品</a>
                            </td>
                        </tr>
                    </table>
                    @endguest

                </div>

            </td>
        </tr>
    </table>

    @yield('content')

</body>

</html>