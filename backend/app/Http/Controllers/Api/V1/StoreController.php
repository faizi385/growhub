<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Get the authenticated seller's store.
     */
    public function myStore(Request $request): JsonResponse
    {
        $store = $request->user()
            ->store()
            ->with('category')
            ->first();

        if (!$store) {
            return response()->json([
                'message' => 'You have not created a store yet.',
                'store' => null,
            ]);
        }

        return response()->json([
            'store' => $store,
        ]);
    }

    /**
     * Create a store for the authenticated seller.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->store()->exists()) {
            return response()->json([
                'message' => 'You have already created a store.',
            ], 409);
        }

        $data = $request->validated();

        unset($data['logo'], $data['cover_image']);

        $data['user_id'] = $user->id;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(6));
        $data['status'] = 'pending';

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')
                ->store('stores/logos', 'public');
        }

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')
                ->store('stores/covers', 'public');
        }

        $store = Store::create($data);

        return response()->json([
            'message' => 'Store created successfully and is awaiting approval.',
            'store' => $store->load('category'),
        ], 201);
    }

    /**
     * Update the authenticated seller's store.
     */
    public function update(StoreUpdateRequest $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'message' => 'You have not created a store yet.',
            ], 404);
        }

        $data = $request->validated();

        unset($data['logo'], $data['cover_image']);

        if (isset($data['name']) && $data['name'] !== $store->name) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(6));
        }

        if ($request->hasFile('logo')) {
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }

            $data['logo'] = $request->file('logo')
                ->store('stores/logos', 'public');
        }

        if ($request->hasFile('cover_image')) {
            if ($store->cover_image) {
                Storage::disk('public')->delete($store->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')
                ->store('stores/covers', 'public');
        }

        $store->update($data);

        return response()->json([
            'message' => 'Store updated successfully.',
            'store' => $store->fresh()->load('category'),
        ]);
    }

    /**
     * Delete the authenticated seller's store.
     */
    public function destroy(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json([
                'message' => 'You have not created a store yet.',
            ], 404);
        }

        if ($store->logo) {
            Storage::disk('public')->delete($store->logo);
        }

        if ($store->cover_image) {
            Storage::disk('public')->delete($store->cover_image);
        }

        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully.',
        ]);
    }
}