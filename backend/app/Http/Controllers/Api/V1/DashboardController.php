<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get seller dashboard statistics and information.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()
            ->store()
            ->with('category:id,name,slug')
            ->first();

        if (!$store) {
            return response()->json([
                'message' => 'Store not found.',
            ], 404);
        }

        $products = $store->products();

        $statistics = [
            'total_products' => (clone $products)->count(),
            'active_products' => (clone $products)
                ->where('status', 'active')
                ->count(),
            'draft_products' => (clone $products)
                ->where('status', 'draft')
                ->count(),
        ];

        $recentProducts = $store->products()
            ->with('category:id,name,slug')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'store' => $store,
            'statistics' => $statistics,
            'recent_products' => $recentProducts,
        ]);
    }
}