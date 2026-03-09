@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')

<table class="page">
    <tr>
        <td class="page__inner">

            <table class="auth-card">
                <tr>
                    <td class="auth-card__inner">

                        <h1 class="auth-title">会員登録</h1>

                        <form action="{{ route('register') }}" method="post" novalidate>
                            @csrf

                            <table class="form-table">
                                <tr>
                                    <th><label for="name">ユーザー名</label></th>
                                </tr>
                                <tr>
                                    <td>
                                        <input
                                            id="name"
                                            type="text"
                                            name="name"
                                            value="{{ old('name') }}">
                                        @error('name')
                                        <p class="form-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>

                                <tr>
                                    <th><label for="email">メールアドレス</label></th>
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
                                    <th><label for="password">パスワード</label></th>
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
                                    <th><label for="password_confirmation">確認用パスワード</label></th>
                                </tr>
                                <tr>
                                    <td>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation">
                                        @error('password_confirmation')
                                        <p class="form-error">{{ $message }}</p>
                                        @enderror
                                    </td>
                                </tr>

                                <tr>
                                    <td class="form-table__actions">
                                        <button type="submit" class="btn-primary">登録する</button>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="form-table__link">
                                        <a href="{{ route('login') }}" class="link">ログインはこちら</a>
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