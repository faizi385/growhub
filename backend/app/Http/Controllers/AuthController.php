<?php

namespace App\Http\Controllers;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    /**
     * Register a seller and create their store.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $logoPath = null;
        $coverPath = null;

        try {
            $result = DB::transaction(function () use (
                $validated,
                $request,
                &$logoPath,
                &$coverPath
            ) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                if ($request->hasFile('logo')) {
                    $logoPath = $request->file('logo')->store('stores/logos', 'public');
                }

                if ($request->hasFile('cover_image')) {
                    $coverPath = $request->file('cover_image')->store('stores/covers', 'public');
                }

                $slug = Str::slug($validated['store_name']);

                do {
                    $uniqueSlug = $slug . '-' . Str::lower(Str::random(6));
                } while (Store::where('slug', $uniqueSlug)->exists());

                $store = Store::create([
                    'user_id' => $user->id,
                    'category_id' => $validated['category_id'],
                    'name' => $validated['store_name'],
                    'slug' => $uniqueSlug,
                    'description' => $validated['description'] ?? null,
                    'seller_type' => $validated['seller_type'],
                    'city' => $validated['city'],
                    'province' => $validated['province'] ?? null,
                    'whatsapp' => $validated['whatsapp'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email_contact'] ?? null,
                    'website' => $validated['website'] ?? null,
                    'instagram' => $validated['instagram'] ?? null,
                    'facebook' => $validated['facebook'] ?? null,
                    'logo' => $logoPath,
                    'cover_image' => $coverPath,
                    'status' => 'pending',
                ]);

                return compact('user', 'store');
            });

            Auth::login($result['user']);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Your GrowHub account and store have been created successfully.',
                'user' => new UserResource($result['user']),
                'store' => $result['store']->load('category'),
            ], 201);

        } catch (Throwable $exception) {
            // Remove uploaded files if the database transaction fails.
            if ($logoPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($logoPath);
            }

            if ($coverPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($coverPath);
            }

            throw $exception;
        }
    }

    /**
     * Authenticate a user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}