@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')

<table class="page">
    <tr>
        <td class="page__inner">

            <table class="auth-card">
                <tr>
                    <td class="auth-card__inner">

                        <h1 class="auth-title">ログイン</h1>

                        <form action="{{ route('login') }}" method="post" novalidate>
                            @csrf

                            <table class="form-table" role="presentation">
                                <tr>
                                    <th>
                                        <label for="email">メールアドレス</label>
                                    </th>
                                </tr>
                                <tr>
                                    <td>
                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            value="{{ old('email') }}">

                                        @error('email')
                                        <p class="form-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <label for="password">パスワード</label>
                                    </th>
                                </tr>
                                <tr>
                                    <td>
                                        <input
                                            id="password"
                                            type="password"
                                            name="password">

                                        @error('password')
                                        <p class="form-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>

                                <tr>
                                    <td class="form-table__actions">
                                        <button type="submit" class="btn-primary">
                                            ログインする
                                        </button>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="form-table__link">
                                        <a href="{{ route('register') }}" class="link">
                                            会員登録はこちら
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </form>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

@endsection