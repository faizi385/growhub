<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->store()->exists() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],

            'price' => ['required', 'numeric', 'min:0'],

            'compare_at_price' => [
                'nullable',
                'numeric',
                'gt:price',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'status' => [
                'sometimes',
                Rule::in(['draft', 'active']),
            ],
        ];
    }
}