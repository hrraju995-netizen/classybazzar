<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderTrackResource;
use App\Models\Order;
use App\Services\OrderInvoicePdfService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    /**
     * POST /api/orders — works for both guests and logged-in customers.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->place($request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Your order has been placed successfully.',
            'order' => new OrderResource($order),
        ], 201);
    }

    /**
     * GET /api/orders — the signed-in customer's order history.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate((int) $request->integer('per_page', 10));

        return response()->json([
            'orders' => OrderResource::collection($orders->items()),
            'meta' => [
                'currentPage' => $orders->currentPage(),
                'lastPage' => $orders->lastPage(),
                'total' => $orders->total(),
                'perPage' => $orders->perPage(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return response()->json([
            'order' => new OrderResource($order->load(['items', 'events'])),
        ]);
    }

    /**
     * POST /api/orders/track
     *
     * Public, but requires the phone number used on the order. Without that
     * check anyone could enumerate order numbers and read strangers' addresses.
     */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $order = Order::query()
            ->with('events')
            ->whereRaw('LOWER(order_number) = ?', [mb_strtolower(trim($data['order_number']))])
            ->where('phone', preg_replace('/\D/', '', $data['phone']))
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'No order found for that order number and phone number.',
            ], 404);
        }

        return response()->json(new OrderTrackResource($order));
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! $order->isCancellable()) {
            return response()->json([
                'message' => 'This order can no longer be cancelled. Please contact support.',
            ], 422);
        }

        $order = $this->orders->updateStatus($order, 'cancelled', 'Cancelled by customer');

        return response()->json([
            'message' => 'Order cancelled.',
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * GET /api/orders/{orderNumber}/invoice — downloads or previews the order invoice PDF.
     */
    public function invoice(Request $request, string $orderNumber, OrderInvoicePdfService $pdf): Response
    {
        $order = Order::query()
            ->with('items')
            ->whereRaw('LOWER(order_number) = ?', [mb_strtolower(trim($orderNumber))])
            ->firstOrFail();

        $inline = $request->boolean('inline');

        return $pdf->response($order, $inline);
    }
}
