<?php

namespace App\Cloud;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Registered on every edition, does nothing unless `APP_EDITION=cloud`.
 * Self-hosted does not even get the Stripe webhook route.
 */
class CloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (config('fleche.edition') !== 'cloud') {
            Cashier::ignoreRoutes();

            return;
        }

        // One-time payment: the checkout session carries the user id.
        Event::listen(WebhookReceived::class, function (WebhookReceived $event): void {
            if (($event->payload['type'] ?? null) !== 'checkout.session.completed') {
                return;
            }

            // Cashier only checks Stripe's signature when a secret is set.
            // Without one, anyone could post a fake "paid" session for any
            // account: grant nothing rather than trust an unsigned payload.
            if (blank(config('cashier.webhook.secret'))) {
                Log::warning('Stripe checkout webhook ignored: STRIPE_WEBHOOK_SECRET is not set.');

                return;
            }

            $session = $event->payload['data']['object'];

            if (($session['payment_status'] ?? null) === 'paid' && isset($session['client_reference_id'])) {
                User::query()->whereKey($session['client_reference_id'])->whereNull('paid_at')->update(['paid_at' => now()]);
            }
        });
    }
}
