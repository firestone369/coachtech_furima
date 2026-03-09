<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            // user_id は Seeder 側で上書きする想定（入れ忘れ防止でダミー値も入れておく）
            'user_id'     => 1,

            // condition / sale_status は仕様の値範囲に合わせて調整してください
            // 例：condition: 1〜3, sale_status: 0/1（販売中/売却済み）など
            'condition'   => $this->faker->numberBetween(1, 3),
            'sale_status' => $this->faker->numberBetween(0, 1),

            'name'        => $this->faker->words(3, true),
            'brand'       => $this->faker->company(),
            'description' => $this->faker->realText(120),

            // price が unsigned int 想定なので 0以上に
            'price'       => $this->faker->numberBetween(300, 50000),
        ];
    }
}
