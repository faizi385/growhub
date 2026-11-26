<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class AdminStoreController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'stats' => [
                'total_stores' => Store::count(),
                'pending_stores' => Store::where('status', 'pending')->count(),
                'approved_stores' => Store::where('status', 'approved')->count(),
                'rejected_stores' => Store::where('status', 'rejected')->count(),
                'suspended_stores' => Store::where('status', 'suspended')->count(),
            ],

            'recent_pending_stores' => Store::where('status', 'pending')
                ->with([
                    'user:id,name,email',
                    'category:id,name',
                ])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Display all stores with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => [
                'nullable',
                Rule::in(['pending', 'approved', 'rejected', 'suspended']),
            ],
            'city' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $stores = Store::query()
            ->with([
                'user:id,name,email',
                'category:id,name,slug',
            ])
            ->withCount([
                'products as products_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');

            $stores->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $stores->where('status', $request->input('status'));
        }

        if ($request->filled('city')) {
            $stores->where('city', $request->input('city'));
        }

        return response()->json(
            $stores->latest()->paginate($request->integer('per_page', 15))
        );
    }

    /**
     * Display a specific store and its seller account.
     */
    public function show(Store $store): JsonResponse
    {
        $store->load([
            'user:id,name,email,created_at,email_verified_at',
            'category:id,name,slug',
            'products' => function ($query) {
                $query->with('category:id,name,slug')->latest();
            },
        ]);

        // Only expose whether a password exists, never its hash.
        if ($store->user) {
            $store->user->setAttribute(
                'has_password',
                filled($store->user->password)
            );

            $store->user->makeHidden('password');
        }

        return response()->json([
            'store' => $store,
        ]);
    }

    /**
     * Update store details and seller account details.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            // Store information
            'name' => ['required', 'string', 'min:2', 'max:150'],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],

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

            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'cover_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            // Seller account information
            'user_name' => ['required', 'string', 'min:2', 'max:255'],

            'user_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($store->user_id),
            ],

            // Leave blank to keep the existing password.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $oldLogo = $store->logo;
        $oldCover = $store->cover_image;

        $newLogo = null;
        $newCover = null;

        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->store('stores/logos', 'public');
            $validated['logo'] = $newLogo;
        }

        if ($request->hasFile('cover_image')) {
            $newCover = $request->file('cover_image')->store('stores/covers', 'public');
            $validated['cover_image'] = $newCover;
        }

        try {
            DB::transaction(function () use ($store, $validated) {
                $store->update([
                    'name' => $validated['name'],
                    'category_id' => $validated['category_id'],
                    'description' => $validated['description'] ?? null,
                    'seller_type' => $validated['seller_type'],
                    'city' => $validated['city'],
                    'province' => $validated['province'] ?? null,
                    'whatsapp' => $validated['whatsapp'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'website' => $validated['website'] ?? null,
                    'instagram' => $validated['instagram'] ?? null,
                    'facebook' => $validated['facebook'] ?? null,
                    'logo' => $validated['logo'] ?? $store->logo,
                    'cover_image' => $validated['cover_image'] ?? $store->cover_image,
                ]);

                $seller = $store->user;

                $seller->name = $validated['user_name'];
                $seller->email = $validated['user_email'];

                if (!empty($validated['password'])) {
                    $seller->password = Hash::make($validated['password']);
                }

                $seller->save();
            });
        } catch (\Throwable $exception) {
            // Remove newly uploaded files if the database update fails.
            if ($newLogo) {
                Storage::disk('public')->delete($newLogo);
            }

            if ($newCover) {
                Storage::disk('public')->delete($newCover);
            }

            throw $exception;
        }

        // Delete replaced images only after the database update succeeds.
        if ($newLogo && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        if ($newCover && $oldCover) {
            Storage::disk('public')->delete($oldCover);
        }

        return response()->json([
            'message' => 'Store and seller account updated successfully.',
            'store' => $store->fresh()->load([
                'user:id,name,email,created_at,email_verified_at',
                'category:id,name,slug',
            ]),
        ]);
    }

    /**
     * Approve a pending store.
     */
    public function approve(Request $request, Store $store): JsonResponse
    {
        if ($store->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending stores can be approved.',
            ], 422);
        }

        $store->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Store approved successfully.',
            'store' => $store->fresh()->load('user:id,name,email'),
        ]);
    }

    /**
     * Reject a pending store.
     */
    public function reject(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($store->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending stores can be rejected.',
            ], 422);
        }

        $store->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return response()->json([
            'message' => 'Store rejected successfully.',
            'store' => $store->fresh(),
        ]);
    }

    /**
     * Suspend an approved store.
     */
    public function suspend(Store $store): JsonResponse
    {
        if ($store->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved stores can be suspended.',
            ], 422);
        }

        $store->update([
            'status' => 'suspended',
        ]);

        return response()->json([
            'message' => 'Store suspended successfully.',
            'store' => $store->fresh(),
        ]);
    }

    /**
     * Reactivate a suspended store.
     */
    public function reactivate(Request $request, Store $store): JsonResponse
    {
        if ($store->status !== 'suspended') {
            return response()->json([
                'message' => 'Only suspended stores can be reactivated.',
            ], 422);
        }

        $store->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'message' => 'Store reactivated successfully.',
            'store' => $store->fresh(),
        ]);
    }

    /**
     * Delete a store.
     */
    public function destroy(Store $store): JsonResponse
    {
        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully.',
        ]);
    }
}