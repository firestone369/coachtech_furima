@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify_email.css') }}">
@endsection

@section('content')
<main class="verify">
    <div class="verify__container">
        <p class="verify__text">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>

        <div class="verify__actions">
            <a class="verify__btn" href="http://localhost:8025" target="_blank" rel="noopener">
                認証はこちらから
            </a>
        </div>

        <form method="POST" action="{{ route('verification.send') }}" class="verify__resend">
            @csrf
            <button type="submit" class="verify__link">認証メールを再送する</button>
        </form>

        @if (session('status') === 'verification-link-sent')
        <p class="verify__status">認証メールを再送しました。</p>
        @endif
    </div>
</main>
@endsection