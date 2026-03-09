@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile_edit.css') }}">
@endsection

@section('content')

<table class="profile-edit-page">
    <tr>
        <td class="profile-edit-page__inner">

            <h1 class="profile-edit-title">プロフィール設定</h1>

            <form action="{{ route('profile.update') }}" method="post" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <table class="profile-edit-icon-area">
                    <tr>
                        <td class="profile-edit-icon-area__left">
                            <div class="profile-edit-icon-preview">
                                @if ($user->profile && $user->profile->icon_path)
                                <img
                                    src="{{ asset('storage/' . $user->profile->icon_path) }}"
                                    alt="user icon"
                                    class="profile-edit-icon-preview__img">
                                @else
                                <div class="profile-edit-icon-preview__placeholder"></div>
                                @endif
                            </div>
                        </td>

                        <td class="profile-edit-icon-area__right">
                            <label class="profile-edit-icon-upload">
                                画像を選択する
                                <input type="file"
                                    name="icon_path"
                                    accept="image/*"
                                    class="profile-edit-icon-upload__input">
                            </label>
                        </td>
                    </tr>

                    {{-- エラーメッセージ専用の横長行 --}}
                    <tr>
                        <td colspan="2" class="profile-edit-icon-area__error">
                            @error('icon_path')
                            <p class="form-error form-error--inline">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>
                </table>


                <table class="profile-edit-form">

                    <tr>
                        <th>ユーザー名</th>
                    </tr>
                    <tr>
                        <td><input type="text" name="name" value="{{ old('name', $user->name) }}">
                            @error('name')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>


                    <tr>
                        <th>郵便番号</th>
                    </tr>
                    <tr>
                        <td><input type="text" name="postcode" value="{{ old('postcode', $profile->postcode ?? '') }}">
                            @error('postcode')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>


                    <tr>
                        <th>住所</th>
                    </tr>
                    <tr>
                        <td><input type="text" name="address" value="{{ old('address', $profile->address ?? '') }}">
                            @error('address')
                            <p class="form-error">{{ $message }}</p>
                            @enderror
                        </td>
                    </tr>


                    <tr>
                        <th>建物名</th>
                    </tr>
                    <tr>
                        <td><input type="text" name="building" value="{{ old('building', $profile->building ?? '') }}"></td>
                    </tr>

                    <tr>
                        <td class="profile-edit-form__actions">
                            <button type="submit" class="profile-edit-btn-update">更新する</button>
                        </td>
                    </tr>

                </table>
            </form>

        </td>
    </tr>
</table>

@endsection