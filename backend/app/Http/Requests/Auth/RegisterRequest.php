<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Account details
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],

            // Store details
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'store_name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'seller_type' => ['required', Rule::in(['physical', 'online', 'both'])],

            // Location
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],

            // Contact details
            'whatsapp' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email_contact' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],

            // Store branding
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
