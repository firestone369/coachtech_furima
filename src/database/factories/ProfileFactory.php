<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        // ダミー画像（GD不要）
        $filename = Str::uuid()->toString() . '.jpg';
        $path = 'profile_icons/' . $filename;
        Storage::disk('public')->put($path, 'dummy profile icon');

        // 000-0000 形式の postcode を必ず生成
        $postcode7 = str_pad(
            (string) $this->faker->numberBetween(0, 9999999),
            7,
            '0',
            STR_PAD_LEFT
        );

        return [
            'icon_path' => $path,
            'postcode'  => substr($postcode7, 0, 3) . '-' . substr($postcode7, 3, 4),
            'address'   => $this->faker->state() . $this->faker->city() . $this->faker->streetAddress(),
            'building'  => $this->faker->optional()->secondaryAddress(),
        ];
    }
}
