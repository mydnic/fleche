<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    // Close the test's transaction before swapping apps (in-memory SQLite even
    // shares its connection with the new one), then open a fresh one exactly
    // like setUp does. A plain `migrate:fresh` would commit instead, and on
    // Postgres its rows would leak into every later test.
    DB::rollBack();
    test()->refreshApplication();
    test()->refreshDatabase();

    foreach ($previous as $key => $value) {
        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}
