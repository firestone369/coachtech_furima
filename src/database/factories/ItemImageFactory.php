<?php

namespace Database\Factories;

use App\Models\ItemImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ItemImageFactory extends Factory
{
    protected $model = ItemImage::class;

    public function definition(): array
    {
        $filename = Str::uuid()->toString() . '.jpg';
        $path = 'images/' . $filename;

        // GD不要：ダミーファイル生成
        Storage::disk('public')->put($path, 'dummy item image');

        return [
            'image_path' => $path,
        ];
    }
}
