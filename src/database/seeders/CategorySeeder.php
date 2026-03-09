<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            1 => 'ファッション',
            2 => '家電',
            3 => 'インテリア',
            4 => 'レディース',
            5 => 'メンズ',
            6 => 'コスメ',
            7 => '本',
            8 => 'ゲーム',
            9 => 'スポーツ',
            10 => 'キッチン',
            11 => 'ハンドメイド',
            12 => 'アクセサリー',
            13 => 'おもちゃ',
            14 => 'ベビー・キッズ',
        ];

        foreach ($names as $id => $name) {
            DB::table('categories')->updateOrInsert(
                ['id' => $id],
                ['name' => $name, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
