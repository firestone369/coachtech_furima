<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // カテゴリが必要なら最初に
        $this->call(CategorySeeder::class);

        // ユーザー作成
        $users = \App\Models\User::factory(10)->create();

        foreach ($users as $user) {

            // プロフィール作成（profiles）
            \App\Models\Profile::factory()->create([
                'user_id' => $user->id,
            ]);

            // 商品作成（items）
            $items = \App\Models\Item::factory(5)->create([
                'user_id' => $user->id,
            ]);

            // 出品画像作成（item_images）＋ カテゴリ紐付け（item_categories）
            foreach ($items as $item) {
                // 画像：1商品につき3枚
                \App\Models\ItemImage::factory(3)->create([
                    'item_id' => $item->id,
                ]);

                // ★カテゴリ：1〜3個をランダムで紐付け
                $categoryIds = Category::query()
                    ->inRandomOrder()
                    ->limit(rand(1, 14))
                    ->pluck('id')
                    ->toArray();

                // 中間テーブル item_categories に登録
                // ※ 既に付いているものがあっても上書きして整えるなら sync
                $item->categories()->sync($categoryIds);
                // 追記だけで良いなら attach を使う：
                // $item->categories()->attach($categoryIds);
            }
        }
    }
}
