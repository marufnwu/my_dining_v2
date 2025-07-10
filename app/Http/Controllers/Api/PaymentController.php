<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManualPaymentRequest;
use App\Http\Requests\GooglePlayPurchaseRequest;
use App\Models\ManualPayment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use App\Services\GooglePlayPaymentService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private GooglePlayPaymentService $googlePlayService;
    private OrderService $orderService;

    public function __construct(
        PaymentService $paymentService,
        GooglePlayPaymentService $googlePlayService,
        OrderService $orderService
    ) {
        $this->paymentService = $paymentService;
        $this->googlePlayService = $googlePlayService;
        $this->orderService = $orderService;
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods()
    {
        $pipeline = $this->paymentService->getAvailablePaymentMethods();
        return $pipeline->toApiResponse();
    }

    /**
     * Submit a manual payment
     */
    public function submitManualPayment(ManualPaymentRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $pipeline = $this->paymentService->submitManualPayment($data);
        return $pipeline->toApiResponse();
    }

    /**
     * Review a manual payment (admin only)
     */
    public function reviewManualPayment(Request $request, ManualPayment $payment)
    {
        $data = $request->validate([
            'status' => ['required', new Enum(PaymentStatus::class)],
            'notes' => 'nullable|string|max:500'
        ]);

        $pipeline = $this->paymentService->reviewManualPayment(
            payment: $payment,
            status: $data['status'],
            reviewer: auth()->user(),
            notes: $data['notes']
        );

        if ($pipeline->isSuccess() && $data['status'] === PaymentStatus::APPROVED->value) {
            // Create order and invoice for approved manual payments
            $subscription = $payment->subscription;

            // Create order
            $orderPipeline = $this->orderService->createOrder($subscription, [
                'payment_method' => $payment->paymentMethod->type,
                'payment_provider' => 'manual',
                'provider_order_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'status' => 'completed'
            ]);

            if ($orderPipeline->isSuccess()) {
                $order = $orderPipeline->getData();

                // Record transaction
                $this->orderService->recordTransaction($order, [
                    'provider_transaction_id' => $payment->transaction_id,
                    'status' => 'completed'
                ]);

                // Generate invoice
                $this->orderService->generateInvoice($order, [
                    'status' => 'paid',
                    'paid_at' => now()
                ]);
            }
        }

        return $pipeline->toApiResponse();
    }

    /**
     * List manual payments
     */
    public function listManualPayments(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', new Enum(PaymentStatus::class)],
            'user_id' => 'nullable|exists:users,id'
        ]);

        $pipeline = $this->paymentService->listManualPayments($filters);
        return $pipeline->toApiResponse();
    }

    /**
     * Update payment method settings (admin only)
     */
    public function updatePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'enabled' => 'sometimes|boolean',
            'instructions' => 'nullable|string',
            'config' => 'nullable|json',
            'display_order' => 'sometimes|integer|min:0'
        ]);

        $paymentMethod->update($data);
        return response()->json(['success' => true, 'data' => $paymentMethod->fresh()]);
    }

    /**
     * Verify a Google Play purchase
     */
    public function verifyGooglePlayPurchase(GooglePlayPurchaseRequest $request)
    {
        $data = $request->validated();
        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);

        $pipeline = $this->googlePlayService->verifySubscription(
            $paymentMethod,
            $data['purchase_token'],
            $data['subscription_id']
        );

        if ($pipeline->isSuccess()) {
            $subscription = $request->user()->messUser->mess->subscription;
            if ($subscription) {
                // Update subscription
                $subscription->update([
                    'google_play_token' => $data['purchase_token'],
                    'google_play_subscription_id' => $data['subscription_id'],
                    'payment_provider' => 'google_play',
                    'payment_status' => PaymentStatus::COMPLETED->value,
                    'starts_at' => $pipeline->getData()['start_time'],
                    'expires_at' => $pipeline->getData()['expiry_time'],
                ]);

                // Create order and generate invoice
                $orderPipeline = $this->orderService->createOrder($subscription, [
                    'payment_method' => 'google_play',
                    'payment_provider' => 'google_play',
                    'provider_order_id' => $data['purchase_token'],
                    'amount' => $pipeline->getData()['price_amount_micros'] / 1000000,
                    'currency' => $pipeline->getData()['price_currency_code'],
                    'status' => 'completed'
                ]);

                if ($orderPipeline->isSuccess()) {
                    $order = $orderPipeline->getData();

                    // Record transaction
                    $this->orderService->recordTransaction($order, [
                        'provider_transaction_id' => $data['purchase_token'],
                        'status' => 'completed'
                    ]);

                    // Generate invoice
                    $this->orderService->generateInvoice($order, [
                        'status' => 'paid',
                        'paid_at' => now()
                    ]);
                }
            }
        }

        return $pipeline->toApiResponse();
    }

    /**
     * Handle Google Play webhook notifications
     */
    public function handleGooglePlayWebhook(Request $request)
    {
        // Verify notification signature
        $payload = $request->getContent();
        $signature = $request->header('X-Goog-Signature');

        if (!$signature || !$this->googlePlayService->verifyNotificationSignature($payload, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $pipeline = $this->googlePlayService->handleSubscriptionNotification($request->all());
        return $pipeline->toApiResponse();
    }
}
