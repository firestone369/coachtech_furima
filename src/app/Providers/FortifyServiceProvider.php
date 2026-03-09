<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Validation\ValidationException;

// Fortify標準LoginRequestを差し替える
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;
use App\Http\Requests\LoginRequest as AppLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // FortifyのLoginRequestをLoginRequestに差し替える
        $this->app->bind(FortifyLoginRequest::class, AppLoginRequest::class);

        $this->app->afterResolving(CreateNewUser::class, function () {
            if (request()->isMethod('post')) {
                app(RegisterRequest::class);
            }
        });
    }

    public function boot(): void
    {
        // 新規登録処理の紐付け
        Fortify::createUsersUsing(CreateNewUser::class);

        // 新規登録後はメール認証案内へ
        Fortify::redirects('register', '/email/verify');

        // ログイン認証ロジック
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            // 認証成功：ユーザーを返す
            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            // 認証失敗：email と password 両方に同じメッセージを付けて返す
            throw ValidationException::withMessages([
                'email'    => 'ログイン情報が登録されていません。',
                'password' => 'ログイン情報が登録されていません。',
            ]);
        });

        // ログイン画面←→新規登録画面 遷移
        Fortify::loginView(fn() => view('auth.login'));
        Fortify::registerView(fn() => view('auth.register'));

        // メール認証案内ページ（/email/verify）新規登録画面→メール認証画面
        Fortify::verifyEmailView(fn() => view('auth.verify-email'));
        // resources/views/auth/verify-email.blade.php

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->email . $request->ip());
        });
    }
}
