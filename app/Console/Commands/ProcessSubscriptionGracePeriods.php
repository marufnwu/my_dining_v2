<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionGracePeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-grace-periods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscription grace periods - move expired subscriptions to grace period and expire those past grace period';

    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing subscription grace periods...');

        // Find subscriptions that should enter grace period
        $subscriptionsToEnterGrace = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->where('payment_status', '!=', Subscription::PAYMENT_STATUS_PAID)
            ->where('is_canceled', false)
            ->get();

        $enteredGraceCount = 0;
        foreach ($subscriptionsToEnterGrace as $subscription) {
            if ($this->subscriptionService->shouldEnterGracePeriod($subscription)) {
                $this->subscriptionService->enterGracePeriod($subscription);
                $enteredGraceCount++;
                $this->line("Subscription #{$subscription->id} entered grace period");
            }
        }

        // Find subscriptions whose grace period has expired
        $subscriptionsToExpire = Subscription::where('status', Subscription::STATUS_GRACE_PERIOD)
            ->where('grace_period_ends_at', '<=', now())
            ->get();

        $expiredCount = 0;
        foreach ($subscriptionsToExpire as $subscription) {
            if ($this->subscriptionService->shouldExpireFromGrace($subscription)) {
                $this->subscriptionService->expireFromGracePeriod($subscription);
                $expiredCount++;
                $this->line("Subscription #{$subscription->id} expired after grace period");
            }
        }

        $this->info("Grace period processing completed:");
        $this->info("- {$enteredGraceCount} subscriptions entered grace period");
        $this->info("- {$expiredCount} subscriptions expired after grace period");

        return Command::SUCCESS;
    }
}
