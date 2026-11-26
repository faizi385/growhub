<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],

            'name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],

            'seller_type' => [
                'required',
                Rule::in(['physical', 'online', 'both']),
            ],

            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],

            'whatsapp' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],

            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],

            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],

            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
