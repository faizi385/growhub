<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStoreController extends Controller
{
    /**
     * Display approved stores with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $stores = Store::query()
            ->where('status', 'approved')
            ->with('category:id,name,slug')
            ->withCount([
                'products as products_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ]);

        // Search by store name.
        if ($request->filled('search')) {
            $stores->where(
                'name',
                'like',
                '%' . $request->input('search') . '%'
            );
        }

        // Filter by category.
        if ($request->filled('category')) {
            $stores->whereHas('category', function ($query) use ($request) {
                $query->where('slug', $request->input('category'));
            });
        }

        // Filter by city.
        if ($request->filled('city')) {
            $stores->where('city', $request->input('city'));
        }

        return response()->json(
            $stores->latest()->paginate(12)->withQueryString()
        );
    }

    /**
     * Display a single approved store and its active products.
     */
    public function show(string $slug): JsonResponse
    {
        $store = Store::query()
            ->where('slug', $slug)
            ->where('status', 'approved')
            ->with('category:id,name,slug')
            ->withCount([
                'products as products_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->firstOrFail();

        $products = $store->products()
            ->where('status', 'active')
            ->with('category:id,name,slug')
            ->latest()
            ->paginate(12);

        return response()->json([
            'store' => $store,
            'products' => $products,
        ]);
    }
}