<?php

use App\Models\TodoSetting;
use App\Models\User;
use Carbon\CarbonImmutable;

it('generates at the hour each user\'s day starts, in their timezone, once', function () {
    $tokyo = User::factory()->create(['timezone' => 'Asia/Tokyo', 'day_start_hour' => 0]);
    $brussels = User::factory()->create(['timezone' => 'Europe/Brussels', 'day_start_hour' => 0]);
    TodoSetting::factory()->for($tokyo)->create(['allow_duplicates' => true]);
    TodoSetting::factory()->for($brussels)->create(['allow_duplicates' => true]);

    // 15:01 UTC = 00:01 in Tokyo (next day), 17:01 in Brussels.
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:01', 'UTC'));
    $this->artisan('fleche:generate');

    expect($tokyo->todos()->sole()->date->toDateString())->toBe('2026-10-02')
        ->and($brussels->todos()->count())->toBe(0);

    // An hour later Tokyo is past midnight: no second roll.
    $this->travelTo(CarbonImmutable::parse('2026-10-01 16:01', 'UTC'));
    $this->artisan('fleche:generate');

    expect($tokyo->todos()->count())->toBe(1);

    // 22:01 UTC = 00:01 in Brussels (summer time).
    $this->travelTo(CarbonImmutable::parse('2026-10-01 22:01', 'UTC'));
    $this->artisan('fleche:generate');

    expect($brussels->todos()->sole()->date->toDateString())->toBe('2026-10-02');
});

it('shows and dates todos in the user\'s timezone', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 23:30', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Tokyo']); // already Oct 2nd, 08:30

    $this->actingAs($user)->post(route('todos.store'), ['name' => 'Gift']);

    expect($user->todos()->sole()->date->toDateString())->toBe('2026-10-03');
    $this->actingAs($user)->get(route('today'))->assertInertia(fn ($page) => $page->where('today', '2026-10-02'));
});

it('saves a valid timezone only', function () {
    $user = User::factory()->create();
    $base = ['notify_mail' => true, 'notify_telegram' => false, 'day_start_hour' => 7];

    $this->actingAs($user)->put(route('settings.notifications'), [...$base, 'timezone' => 'Mars/Olympus'])->assertSessionHasErrors('timezone');
    $this->actingAs($user)->put(route('settings.notifications'), [...$base, 'timezone' => 'America/Montreal']);

    expect($user->refresh()->timezone)->toBe('America/Montreal');
});

it('keeps the browser timezone on register', function () {
    $this->post('/app/register', [
        'name' => 'A', 'email' => 'a@b.c', 'password' => 'password', 'password_confirmation' => 'password', 'timezone' => 'Asia/Tokyo',
    ]);

    expect(User::sole()->timezone)->toBe('Asia/Tokyo');
});
