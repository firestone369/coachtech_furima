<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_postcode' => ['required', 'string', 'regex:/^\d{3}-\d{4}$/'],
            'delivery_address'  => ['required', 'string', 'max:255'],
            'delivery_building' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_postcode.required' => '郵便番号を入力してください',
            'delivery_postcode.regex' => '郵便番号は 000-0000 の形式で入力してください',
            'delivery_address.required' => '住所を入力してください',
            'delivery_address.max' => '住所は255文字以内で入力してください',
            'delivery_building.max' => '建物名は255文字以内で入力してください',
        ];
    }
}
