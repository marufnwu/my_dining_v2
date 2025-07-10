<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\ManualPayment;
use App\Models\PaymentMethod;
use App\Models\SubscriptionOrder;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ManualPayment::class => PaymentPolicy::class,
        PaymentMethod::class => PaymentPolicy::class,
        SubscriptionOrder::class => OrderPolicy::class,
        Invoice::class => InvoicePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
