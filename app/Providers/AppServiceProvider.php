<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // Behind a TLS-terminating proxy: links (password reset) follow APP_URL,
        // never a client-controlled X-Forwarded-Host.
        //
        // HTTP requests only. The console already roots URLs at APP_URL (queued
        // mails included), and forcing it there makes `wayfinder:generate`, run
        // at build time, bake absolute URLs into the front end: nav items then
        // never match the relative page URL and Nuxt UI treats links as external.
        if (! $this->app->runningInConsole() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
            URL::forceRootUrl((string) config('app.url'));
        }

        JsonResource::withoutWrapping();
    }
}
