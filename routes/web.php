<?php

use App\Enums\HubPackStatus;
use App\Http\Controllers\HubController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TodoController;
use App\Models\HubPack;
use Illuminate\Support\Facades\Route;

/*
 * Public site: Blade, cloud only. Self-hosted has nothing to sell, so `/`
 * goes straight to the app. Checked per request so `route:cache` still works.
 */
Route::get('/', fn () => config('fleche.edition') === 'cloud'
    ? view('landing', [
        // The fastest way to show what rules can do: real packs, ready to import.
        'packs' => HubPack::query()
            ->where('status', HubPackStatus::Approved)
            ->orderByDesc('imports_count')
            ->oldest('id')
            ->limit(6)
            ->get(),
    ])
    : redirect('/app'))->name('home');

// Legal pages describe fleche.io; a self-hosted operator writes their own.
foreach (['privacy', 'terms'] as $page) {
    Route::get($page, fn () => config('fleche.edition') === 'cloud'
        ? view("legal.{$page}")
        : redirect('/app'))->name($page);
}

Route::prefix('app')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('setup', [SetupController::class, 'create'])->name('setup');
        Route::post('setup', [SetupController::class, 'store'])->name('setup.store');
    });

    Route::middleware('auth')->group(function (): void {
        Route::get('/', [TodoController::class, 'index'])->name('today');
        Route::post('todos', [TodoController::class, 'store'])->name('todos.store');
        Route::post('todos/{todo}/done', [TodoController::class, 'done'])->name('todos.done');

        Route::put('rules/groups', [RuleController::class, 'group'])->name('rules.groups');
        Route::post('rules/{rule}/duplicate', [RuleController::class, 'duplicate'])->name('rules.duplicate');
        Route::resource('rules', RuleController::class)->except('show');

        Route::get('stats', [StatsController::class, 'index'])->name('stats');

        Route::get('hub', [HubController::class, 'index'])->name('hub');
        Route::post('hub/{pack}/import', [HubController::class, 'import'])->whereNumber('pack')->name('hub.import');
        Route::post('hub', [HubController::class, 'publish'])->name('hub.publish');
        Route::patch('hub/{pack}', [HubController::class, 'moderate'])->name('hub.moderate');
        Route::delete('hub/{pack}', [HubController::class, 'destroy'])->name('hub.destroy');

        Route::get('settings', [SettingsController::class, 'show'])->name('settings');
        Route::put('settings/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
        Route::delete('settings/telegram', [SettingsController::class, 'disconnectTelegram'])->name('settings.telegram.disconnect');
        Route::post('settings/tokens', [SettingsController::class, 'createToken'])->name('settings.tokens.store');
        Route::delete('settings/tokens/{token}', [SettingsController::class, 'deleteToken'])->name('settings.tokens.destroy');
        Route::post('settings/checkout', [SettingsController::class, 'checkout'])->name('settings.checkout');
        Route::delete('settings/account', [SettingsController::class, 'destroyAccount'])->name('settings.account.destroy');
    });
});
