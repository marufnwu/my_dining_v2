<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Helpers\Pipeline;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use Google\Client as GoogleClient;
use Google\Service\AndroidPublisher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GooglePlayPaymentService
{
    protected $client;
    protected $androidPublisher;

    public function __construct()
    {
        $this->initializeGoogleClient();
    }

    /**
     * Initialize Google API client
     */
    protected function initializeGoogleClient(): void
    {
        $this->client = new GoogleClient();
        $this->client->setAuthConfig(storage_path('app/google-play-credentials.json'));
        $this->client->addScope('https://www.googleapis.com/auth/androidpublisher');

        $this->androidPublisher = new AndroidPublisher($this->client);
    }

    /**
     * Verify a Google Play purchase token
     */
    public function verifySubscription(PaymentMethod $paymentMethod, string $purchaseToken, string $subscriptionId): Pipeline
    {
        try {
            $config = json_decode($paymentMethod->config, true);
            $packageName = $config['package_name'] ?? env('GOOGLE_PLAY_PACKAGE_NAME');

            if (!$packageName) {
                return Pipeline::error('Google Play package name not configured');
            }

            $subscription = $this->androidPublisher->purchases_subscriptions->get(
                $packageName,
                $subscriptionId,
                $purchaseToken
            );

            // Check if subscription is valid
            if ($subscription->getPaymentState() !== 1) { // 1 = Payment received
                return Pipeline::error('Invalid payment state');
            }

            $startTimeMillis = $subscription->getStartTimeMillis();
            $expiryTimeMillis = $subscription->getExpiryTimeMillis();

            return Pipeline::success([
                'status' => PaymentStatus::COMPLETED,
                'start_time' => $this->millisecondsToDateTime($startTimeMillis),
                'expiry_time' => $this->millisecondsToDateTime($expiryTimeMillis),
                'auto_renewing' => $subscription->getAutoRenewing(),
                'price_currency_code' => $subscription->getPriceCurrencyCode(),
                'price_amount_micros' => $subscription->getPriceAmountMicros(),
            ]);
        } catch (\Exception $e) {
            Log::error('Google Play verification error: ' . $e->getMessage());
            return Pipeline::error('Failed to verify Google Play purchase: ' . $e->getMessage());
        }
    }

    /**
     * Handle subscription webhook from Google Play
     */
    public function handleSubscriptionNotification(array $notification): Pipeline
    {
        try {
            $subscriptionId = $notification['subscriptionId'];
            $purchaseToken = $notification['purchaseToken'];
            $notificationType = $notification['notificationType'];

            // Find subscription by Google Play purchase token
            $subscription = Subscription::where('google_play_token', $purchaseToken)->first();
            if (!$subscription) {
                return Pipeline::error('Subscription not found');
            }

            switch ($notificationType) {
                case 2: // Renewed
                    $subscription->status = Subscription::STATUS_ACTIVE;
                    $subscription->expires_at = $this->millisecondsToDateTime($notification['expiryTimeMillis']);
                    $subscription->save();

                    // Generate new invoice for the renewal
                    $subscription->generateInvoice();
                    break;

                case 3: // Cancelled
                    $subscription->is_canceled = true;
                    $subscription->status = Subscription::STATUS_CANCELED;
                    $subscription->canceled_at = now();
                    $subscription->save();
                    break;

                case 13: // Grace period
                    $subscription->status = Subscription::STATUS_GRACE_PERIOD;
                    $subscription->grace_period_ends_at = $this->millisecondsToDateTime($notification['expiryTimeMillis']);
                    $subscription->save();
                    break;

                case 12: // Account hold
                    if ($subscription->gracePeriodExpired()) {
                        $subscription->status = Subscription::STATUS_UNPAID;
                        $subscription->is_active = false;
                        $subscription->save();
                    }
                    break;

                case 4: // Refunded
                    $subscription->status = Subscription::STATUS_CANCELED;
                    $subscription->is_canceled = true;
                    $subscription->canceled_at = now();
                    $subscription->save();

                    // Update latest transaction and invoice to refunded
                    if ($latestOrder = $subscription->latestOrder) {
                        $latestOrder->update(['status' => SubscriptionOrder::STATUS_REFUNDED]);

                        if ($transaction = $latestOrder->transactions()->latest()->first()) {
                            $transaction->update(['status' => Transaction::STATUS_REFUNDED]);
                        }

                        if ($invoice = $latestOrder->invoice) {
                            $invoice->update(['status' => Invoice::STATUS_REFUNDED]);
                        }
                    }
                    break;
            }

            return Pipeline::success();
        } catch (\Exception $e) {
            Log::error('Google Play webhook error: ' . $e->getMessage());
            return Pipeline::error('Failed to process Google Play notification: ' . $e->getMessage());
        }
    }

    /**
     * Process a refund for a Google Play subscription
     */
    public function processRefund(Subscription $subscription): Pipeline
    {
        try {
            if (!$subscription->google_play_token || !$subscription->google_play_subscription_id) {
                return Pipeline::error('Not a Google Play subscription');
            }

            $packageName = env('GOOGLE_PLAY_PACKAGE_NAME');
            if (!$packageName) {
                return Pipeline::error('Google Play package name not configured');
            }

            // Refund the latest purchase
            $this->androidPublisher->purchases_subscriptions->refund(
                $packageName,
                $subscription->google_play_subscription_id,
                $subscription->google_play_token
            );

            // The webhook will handle updating the subscription status
            return Pipeline::success();
        } catch (\Exception $e) {
            Log::error('Google Play refund error: ' . $e->getMessage());
            return Pipeline::error('Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Verify the authenticity of Google Play notifications
     */
    public function verifyNotificationSignature(string $messagePayload, string $signature): bool
    {
        try {
            // Get Google's public keys (with caching)
            $publicKeys = Cache::remember('google_play_public_keys', 3600, function () {
                $response = file_get_contents('https://www.googleapis.com/robot/v1/metadata/x509/google-play-developer-notifications@system.gserviceaccount.com');
                return json_decode($response, true);
            });

            // Try each public key
            foreach ($publicKeys as $key) {
                $publicKey = openssl_pkey_get_public($key);
                if (!$publicKey) continue;

                // Verify signature
                $verify = openssl_verify(
                    $messagePayload,
                    base64_decode($signature),
                    $publicKey,
                    OPENSSL_ALGO_SHA256
                );

                openssl_free_key($publicKey);

                if ($verify === 1) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Signature verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert Google Play milliseconds timestamp to DateTime
     */
    protected function millisecondsToDateTime(int $milliseconds): \DateTime
    {
        return \DateTime::createFromFormat('U', (int)($milliseconds / 1000));
    }
}
