<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use App\Models\Invoice;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * List orders for the authenticated user's mess
     */
    public function listOrders(Request $request)
    {
        $query = SubscriptionOrder::where('mess_id', app()->getMess()->id)
            ->with(['subscription', 'plan', 'package'])
            ->latest();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10);
        return response()->json($orders);
    }

    /**
     * Get order details
     */
    public function getOrder(SubscriptionOrder $order)
    {
        // Authorize the request
        $this->authorize('view', $order);

        $order->load(['subscription', 'plan', 'package', 'transactions', 'invoice']);
        return response()->json($order);
    }

    /**
     * Get invoice for an order
     */
    public function getInvoice(Invoice $invoice)
    {
        // Authorize the request
        $this->authorize('view', $invoice);

        $invoice->load(['order', 'transaction']);
        return response()->json($invoice);
    }

    /**
     * Update order status (admin only)
     */
    public function updateOrderStatus(Request $request, SubscriptionOrder $order)
    {
        // Authorize the request
        $this->authorize('update', $order);

        $data = $request->validate([
            'status' => 'required|string',
            'error_message' => 'nullable|string'
        ]);

        $pipeline = $this->orderService->updateOrderStatus(
            order: $order,
            status: $data['status'],
            errorMessage: $data['error_message'] ?? null
        );

        return $pipeline->toApiResponse();
    }
}
