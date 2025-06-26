<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessAutomaticBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-automatic-billing
                            {--days-advance=7 : Days in advance to generate invoices}
                            {--send-reminders : Send invoice reminders}
                            {--retry-failed : Retry failed payments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic billing: generate invoices, attempt payments, send reminders';

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
        $this->info('Starting automatic billing process...');

        $daysAdvance = (int) $this->option('days-advance');

        // Process automatic billing
        $results = $this->subscriptionService->processAutomaticBilling($daysAdvance);

        $this->displayBillingResults($results);

        // Send reminders if requested
        if ($this->option('send-reminders')) {
            $this->info('Sending invoice reminders...');
            $reminderResults = $this->subscriptionService->sendInvoiceReminders();
            $this->line("Reminders sent: {$reminderResults['reminders_sent']}");
        }

        // Retry failed payments if requested
        if ($this->option('retry-failed')) {
            $this->info('Processing failed payment retries...');
            $this->processFailedPaymentRetries();
        }

        $this->info('Automatic billing process completed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Display billing results in formatted table.
     *
     * @param array $results
     */
    protected function displayBillingResults(array $results): void
    {
        $this->info('Billing Process Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Subscriptions Processed', $results['processed']],
                ['Invoices Generated', $results['invoices_generated']],
                ['Payment Attempts', $results['payments_attempted']],
                ['Successful Payments', $results['payments_successful']],
                ['Failed Payments', $results['payments_failed']],
                ['Errors', count($results['errors'])],
            ]
        );

        if (!empty($results['errors'])) {
            $this->warn('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->line("- Subscription {$error['subscription_id']}: {$error['message']}");
            }
        }
    }

    /**
     * Process failed payment retries.
     */
    protected function processFailedPaymentRetries(): void
    {
        // This would implement retry logic for failed payments
        $this->line('Failed payment retry processing would be implemented here');
    }
}
