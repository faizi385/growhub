<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    /**
     * Display public products with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('products.status', 'active')
            ->whereHas('store', function ($query) {
                $query->where('status', 'approved');
            })
            ->with([
                'category:id,name,slug',
                'store:id,name,slug,city,province,whatsapp,logo',
            ]);

        // Search by product name.
        if ($request->filled('search')) {
            $products->where(
                'products.name',
                'like',
                '%' . $request->string('search') . '%'
            );
        }

        // Filter by category.
        if ($request->filled('category')) {
            $products->whereHas('category', function ($query) use ($request) {
                $query->where('slug', $request->input('category'));
            });
        }

        // Filter by city.
        if ($request->filled('city')) {
            $products->whereHas('store', function ($query) use ($request) {
                $query->where('city', $request->input('city'));
            });
        }

        // Filter by minimum price.
        if ($request->filled('min_price')) {
            $products->where('products.price', '>=', $request->input('min_price'));
        }

        // Filter by maximum price.
        if ($request->filled('max_price')) {
            $products->where('products.price', '<=', $request->input('max_price'));
        }

        // Sorting.
        match ($request->input('sort', 'latest')) {
            'price_low' => $products->orderBy('products.price'),
            'price_high' => $products->orderByDesc('products.price'),
            'name' => $products->orderBy('products.name'),
            default => $products->latest('products.created_at'),
        };

        return response()->json(
            $products->paginate(12)->withQueryString()
        );
    }

    /**
     * Display a single public product.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->whereHas('store', function ($query) {
                $query->where('status', 'approved');
            })
            ->with([
                'category:id,name,slug',
                'store:id,name,slug,description,city,province,whatsapp,phone,email,website,instagram,facebook,logo,cover_image',
            ])
            ->firstOrFail();

        return response()->json([
            'product' => $product,
        ]);
    }
}