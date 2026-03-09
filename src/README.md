# coachtechフリマアプリ

## 環境構築


### Dockerビルド
- cd coachtech_furima
- docker-compose up -d --build


### Laravel環境構築
- docker compose exec php bash
- composer install
- cp .env.example .env
- php artisan key:generate
- php artisan migrate --seed


## 使用技術（実行環境）
- Laravel 8.x
- PHP 8.x
- Mysql 8.x
- Docker / Docker Compose
- Nginx 1.21.1
- MailHog


## 認証機能
- Laravel Fortify を用いたユーザー認証
- 新規登録時にメール認証を実施
- 認証メールの確認には MailHog を使用


## ER図
- /README_assets/er_diagram.png


## URL
- トップページ（商品一覧）：http://localhost/
- ユーザー登録画面：http://localhost/register
- ログイン画面：http://localhost/login
- phpMyAdmin：http://localhost:8080


## 補足
- 本アプリでは会員登録時にメール認証を行います
- 開発環境ではメールは送信されず、storage/logs/laravel.log に認証URLが出力されます