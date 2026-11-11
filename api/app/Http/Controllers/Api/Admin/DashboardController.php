<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $revenue = Order::query()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->sum('total');

        $revenueThisMonth = Order::query()
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        return response()->json([
            'stats' => [
                'totalRevenue' => Money::toTaka((int) $revenue),
                'revenueThisMonth' => Money::toTaka((int) $revenueThisMonth),
                'totalOrders' => Order::count(),
                'pendingOrders' => Order::where('status', 'pending')->count(),
                'totalProducts' => Product::count(),
                'outOfStock' => Product::where('stock', 0)->count(),
                'lowStock' => Product::whereBetween('stock', [1, 5])->count(),
                'totalCustomers' => User::where('is_admin', false)->count(),
                'unreadMessages' => ContactMessage::where('is_read', false)->count(),
            ],
            'recentOrders' => OrderResource::collection(
                Order::with('items')->latest()->limit(10)->get()
            ),
            'topProducts' => Product::query()
                ->orderByDesc('sold_count')
                ->limit(5)
                ->get(['id', 'title', 'sold_count', 'stock'])
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'sold' => (int) $p->sold_count,
                    'stock' => (int) $p->stock,
                ]),
        ]);
    }
}
