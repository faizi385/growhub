<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->store()->exists() ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],

            'seller_type' => [
                'sometimes',
                'required',
                Rule::in(['physical', 'online', 'both']),
            ],

            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],

            'whatsapp' => ['sometimes', 'required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],

            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'cover_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}