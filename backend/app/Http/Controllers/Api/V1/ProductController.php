<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Get authenticated seller's products.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (! $store) {
            return response()->json([
                'message' => 'You do not have a store.',
            ], 404);
        }

        $products = $store->products()
            ->with('category:id,name,slug')
            ->latest()
            ->paginate(12);

        return response()->json($products);
    }

    /**
     * Create a new product.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $store = $request->user()->store;

        if (! $store) {
            return response()->json([
                'message' => 'You do not have a store.',
            ], 404);
        }

        $data = $request->validated();

        $data['slug'] = $this->generateSlug($data['name']);
        $data['image'] = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        $product = $store->products()->create($data);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product->load('category:id,name,slug'),
        ], 201);
    }

    /**
     * Show a seller's product.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductOwnership($request, $product);

        return response()->json([
            'product' => $product->load('category:id,name,slug'),
        ]);
    }

    /**
     * Update a seller's product.
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $this->ensureProductOwnership($request, $product);

        $data = $request->validated();

        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $data['image'] = $request->file('image')
                ->store('products', 'public');
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product->fresh()->load('category:id,name,slug'),
        ]);
    }

    /**
     * Delete a seller's product.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureProductOwnership($request, $product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Ensure the product belongs to the authenticated seller.
     */
    private function ensureProductOwnership(
        Request $request,
        Product $product
    ): void {
        abort_unless(
            $request->user()->store?->id === $product->store_id,
            404
        );
    }

    /**
     * Generate a unique product slug.
     */
    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $slug = $slug ?: 'product';

        $uniqueSlug = $slug;
        $counter = 1;

        while (Product::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slug . '-' . $counter;
            $counter++;
        }

        return $uniqueSlug;
    }
}