<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /* GET /mypage/profile（編集画面） */
    public function edit()
    {
        $user = Auth::user()->load('profile');
        $profile = $user->profile;

        return view('profile.edit', compact('user', 'profile'));
    }

    /* PATCH /mypage/profile（更新） */
    public function update(ProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // users.name を更新（profilesテーブルには入れない）
        if (array_key_exists('name', $data)) {
            $user->update(['name' => $data['name']]);
            unset($data['name']);
        }

        // 画像アップロード
        if ($request->hasFile('icon_path')) {

            // 既存画像があれば削除
            if ($user->profile && $user->profile->icon_path) {
                Storage::disk('public')->delete($user->profile->icon_path);
            }

            // storage/app/public/profile_icons/... に保存
            $path = $request->file('icon_path')->store('profile_icons', 'public');

            // DBには "profile_icons/xxxxx.jpg" を保存
            $data['icon_path'] = $path;
        } else {
            // ファイルが無い時は更新しない（既存保持）
            unset($data['icon_path']);
        }

        // profiles 更新/作成
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()->route('mypage.show', ['page' => 'sell']);
    }
}
