<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'icon_path' => ['nullable', 'image', 'mimes:jpeg,png'],
            'name' => ['required', 'string', 'max:20'],
            'postcode' => ['required', 'string', 'regex:/^\d{3}-\d{4}$/'],
            'address' => ['required', 'string'],
            'building' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'icon_path.image' => '画像ファイルを選択してください',
            'icon_path.mimes' => 'jpeg、またはpng形式の画像を選んでください',
            'name.required' => 'ユーザー名をご入力ください',
            'postcode.required' => '郵便番号をご入力ください',
            'postcode.regex' => '郵便番号は算用数字とハイフンを用いて、 000-0000 の形式で入力してください',
            'address.required' => '住所をご入力ください',
        ];
    }
}
