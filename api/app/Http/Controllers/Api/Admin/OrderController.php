<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->query('q').'%';
                $q->where(fn ($sub) => $sub->where('order_number', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('customer_name', 'like', $term));
            })
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return response()->json([
            'orders' => OrderResource::collection($orders->items()),
            'meta' => [
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'order' => new OrderResource($order->load(['items', 'events'])),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
            'description' => ['nullable', 'string', 'max:255'],
            'carrier' => ['nullable', 'string', 'max:80'],
            'tracking_code' => ['nullable', 'string', 'max:120'],
        ]);

        $order->fill(array_filter([
            'carrier' => $data['carrier'] ?? null,
            'tracking_code' => $data['tracking_code'] ?? null,
        ]))->save();

        $order = $this->orders->updateStatus($order, $data['status'], $data['description'] ?? null);

        return response()->json([
            'message' => 'Order status updated.',
            'order' => new OrderResource($order),
        ]);
    }
}
