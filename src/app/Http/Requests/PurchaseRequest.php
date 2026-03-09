<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Purchase;

class PurchaseRequest extends FormRequest
{
    private const PAYMENT_CONVENI = 1;
    private const PAYMENT_CREDIT  = 2;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method'    => ['required', 'integer', 'in:' . self::PAYMENT_CONVENI . ',' . self::PAYMENT_CREDIT],
            'delivery_postcode' => ['required', 'string', 'max:8'],
            'delivery_address'  => ['required', 'string'],
            'delivery_building' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => '支払い方法を選択してください',
            'delivery_postcode.max' => '郵便番号は8文字以内で入力してください。',
        ];
    }
}
