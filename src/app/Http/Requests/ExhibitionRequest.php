<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;

class ExhibitionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png', 'required_without:tmp_image'],
            'tmp_image' =>['nullable','string'],
            'category_ids'   => ['required', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'condition' => ['required', 'in:1,2,3,4'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required_without' => '商品画像を選択してください',
            'image.image' => '画像ファイルを選択してください',
            'image.mimes' => 'jpeg、またはpng形式の画像を選んでください',

            'name.required' => '商品名を入力してください',
            'name.max' => '商品名は255文字以内で入力してください',

            'description.required' => '商品説明を入力してください',
            'description.max' => '商品説明は255文字以内です',

            'category_ids.required' => 'カテゴリーを選択してください',

            'condition.required' => '商品の状態を選んでください',

            'price.required' => '価格を入力してください',
            'price.integer' => '価格は数字で入力してください',
            'price.min' => '価格は0円以上にしてください',

        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $request = $this;

        // 画像が選択されているなら tmp 保存してセッション保持
        if ($request->hasFile('image')) {
            // 以前のtmpがあれば消す（ゴミ掃除）
            $old = session('tmp_image');
            if ($old && Storage::disk('public')->exists($old)) {
                Storage::disk('public')->delete($old);
            }

            $path = $request->file('image')->store('tmp', 'public'); // tmp/xxxx.png
            session(['tmp_image' => $path]);

            // old入力にも tmp_image を混ぜる（hidden用）
            $request->merge([
                'tmp_image' => $path,
            ]);
        }

        // FormRequest既定の挙動（redirect back + errors + input）を自前で再現
        throw new HttpResponseException(
            redirect()
                ->back()
                ->withErrors($validator)
                ->withInput($request->except('image')) // fileは保持不可なので除外
        );
    }

}
