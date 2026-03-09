@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items_create.css') }}">
@endsection

@section('content')
<table class="create-page">
    <tr>
        <td class="create-page__inner">

            <h1 class="create-title">商品の出品</h1>

            <form action="{{ route('items.store') }}" method="post" enctype="multipart/form-data" class="create-form" novalidate>
                @csrf

                <table class="create-block">
                    <tr>
                        <td class="create-label">商品画像</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="create-image-box">
                                <label class="create-image-button">
                                    画像を選択する
                                    <input type="file" name="image" accept="image/*" class="create-image-input">
                                </label>
                            </div>

                            {{-- tmp画像パス保持用（表示はしない） --}}
                            @php
                            $tmpImage = old('tmp_image', session('tmp_image'));
                            @endphp

                            @if($tmpImage)
                            <input type="hidden" name="tmp_image" value="{{ $tmpImage }}">
                            @endif

                            @error('image')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>

                <table class="create-section--border-bottom">
                    <tr>
                        <td class="create-section__title">商品の詳細</td>
                    </tr>
                </table>
                <table class="create-block">
                    <tr>
                        <td class="create-label">カテゴリー</td>
                    </tr>
                </table>

                @php
                $categories = [
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

                $oldCategories = array_map('intval', old('category_ids', []));
                @endphp

                <div class="create-tags">
                    @foreach($categories as $id => $label)
                    <span class="create-tag-cell">
                        <input
                            class="create-tag-check"
                            type="checkbox"
                            name="category_ids[]"
                            value="{{ $id }}"
                            id="cat{{ $id }}"
                            {{ in_array($id, $oldCategories, true) ? 'checked' : '' }}>
                        <label class="create-tag" for="cat{{ $id }}">{{ $label }}</label>
                    </span>
                    @endforeach
                </div>

                <div>
                    @error('category_ids')
                    <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>



                <table class="create-section">
                    <tr>
                        <td class="create-section-condition__title">商品の状態</td>
                    </tr>
                </table>

                <table class="create-block">
                    <tr>
                        <td>
                            <select name="condition" class="create-select" required>
                                <option value="" disabled selected {{ old('condition') === null ? 'selected' : '' }}>
                                    選択してください
                                </option>
                                <option value="1" {{ old('condition') == 1 ? 'selected' : '' }}>良好</option>
                                <option value="2" {{ old('condition') == 2 ? 'selected' : '' }}>目立った傷や汚れなし</option>
                                <option value="3" {{ old('condition') == 3 ? 'selected' : '' }}>やや傷や汚れあり</option>
                                <option value="4" {{ old('condition') == 4 ? 'selected' : '' }}>状態が悪い</option>
                            </select>
                            @error('condition')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>

                <table class="create-section create-section--border-bottom">
                    <tr>
                        <td class="create-section__title">商品名と説明</td>
                    </tr>
                </table>

                <table class="create-block">
                    <tr>
                        <td class="create-label">商品名</td>
                    </tr>
                    <tr>
                        <td><input name="name" type="text" class="create-input" value="{{ old('name') }}">
                            @error('name')
                            <p class=" form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>

                <table class="create-block">
                    <tr>
                        <td class="create-label">ブランド名</td>
                    </tr>
                    <tr>
                        <td><input name="brand" type="text" class="create-input" value="{{ old('brand') }}"></td>
                    </tr>
                </table>

                <table class=" create-block">
                    <tr>
                        <td class="create-label">商品の説明</td>
                    </tr>
                    <tr>
                        <td><textarea name="description" class="create-textarea" rows="6">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>

                <table class="create-block">
                    <tr>
                        <td class="create-label">販売価格</td>
                    </tr>
                    <tr>
                        <td>
                            <table class="create-price-table">
                                <tr>
                                    <td>
                                        <div class="price-input-wrap">
                                            <span class="price-input-yen">¥</span>
                                            <input
                                                type="number"
                                                name="price"
                                                class="price-input"
                                                value="{{ old('price') }}">
                                        </div>

                                        @error('price')
                                        <p class="form-error">{{ $message }}</p>
                                        @enderror
                                    </td>

                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table class="create-actions">
                    <tr>
                        <td>
                            <button type="submit" class="create-submit">出品する</button>
                        </td>
                    </tr>
                </table>

            </form>

        </td>
    </tr>
</table>
@endsection