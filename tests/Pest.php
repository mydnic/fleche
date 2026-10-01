<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Edition is read while providers register (Stripe routes, webhook listener,
 * Fortify features), so switching it means booting the app again. Both
 * variables are pinned in phpunit.xml so `.env` never overrides them.
 */
function bootWithEnv(array $env): void
{
    $previous = [];

    foreach ($env as $key => $value) {
        $previous[$key] = getenv($key);
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    test()->refreshApplication();
    test()->artisan('migrate:fresh');

    foreach ($previous as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}
