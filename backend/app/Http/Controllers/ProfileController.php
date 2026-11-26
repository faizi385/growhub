<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's name and email.
     * Changing the email requires the current password.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $emailChanged = strcasecmp((string) $request->input('email'), $user->email) !== 0;

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => $emailChanged
                ? ['required', 'current_password']
                : ['nullable'],
        ], [
            'current_password.required' => 'Enter your password to change your email.',
            'current_password.current_password' => 'That password is incorrect.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Your profile was updated.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Change the authenticated user's password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->numbers(),
            ],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // New session ID after a credential change (the CSRF token is kept,
        // so the SPA's next request still works).
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'message' => 'Your password was changed.',
        ]);
    }
}