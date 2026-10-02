<?php

use App\Models\Todo;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00', 'UTC')));

function hit(User $user, string $doneAt, string $name = 'x'): void
{
    Todo::factory()->for($user)->create(['name' => $name, 'date' => substr($doneAt, 0, 10), 'done_at' => CarbonImmutable::parse($doneAt, 'UTC')]);
}

it('counts hits per local day over the last 90 days', function () {
    $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);
    hit($user, '2026-10-01 16:00'); // already Oct 2nd in Tokyo
    hit($user, '2026-10-02 01:00');
    hit($user, '2026-09-30 10:00');

    $this->actingAs($user)->get(route('stats'))->assertInertia(fn ($page) => $page
        ->component('Stats')
        ->has('days', 90)
        ->where('days.89', ['date' => '2026-10-02', 'hits' => 2])
        ->where('days.87', ['date' => '2026-09-30', 'hits' => 1])
        ->where('totalHits', 3));
});

it('computes current and best streaks; today without a hit yet keeps the streak alive', function () {
    $user = User::factory()->create();
    foreach (['2026-09-20', '2026-09-21', '2026-09-22', '2026-09-23'] as $day) {
        hit($user, "{$day} 09:00");
    }
    hit($user, '2026-09-30 09:00');
    hit($user, '2026-10-01 09:00', 'Stretch');
    hit($user, '2026-10-01 10:00', 'Stretch');

    $this->actingAs($user)->get(route('stats'))->assertInertia(fn ($page) => $page
        ->where('currentStreak', 2)
        ->where('bestStreak', 4)
        ->where('topRules', [['name' => 'x', 'hits' => 5], ['name' => 'Stretch', 'hits' => 2]]));
});

it('breaks the current streak after a missed day', function () {
    $user = User::factory()->create();
    hit($user, '2026-09-29 09:00');

    $this->actingAs($user)->get(route('stats'))->assertInertia(fn ($page) => $page->where('currentStreak', 0)->where('bestStreak', 1));
});

it('gives the share of the last 30 days\' todos that got done', function () {
    $user = User::factory()->create();
    hit($user, '2026-10-01 09:00');
    Todo::factory()->for($user)->create(['date' => '2026-10-01']);
    Todo::factory()->for($user)->create(['date' => '2026-10-02']);
    Todo::factory()->for($user)->create(['date' => '2026-08-01']); // too old
    Todo::factory()->for($user)->create(['date' => '2026-10-05']); // not yet

    $this->actingAs($user)->get(route('stats'))->assertInertia(fn ($page) => $page->where('hitRate', 33));
});

it('shows free cloud accounts where their history stops', function () {
    $this->actingAs(User::factory()->create())->get(route('stats'))->assertInertia(fn ($page) => $page->where('freeDays', null));

    bootWithEnv(['APP_EDITION' => 'cloud']);
    $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00', 'UTC'));

    $this->actingAs(User::factory()->create())->get(route('stats'))->assertInertia(fn ($page) => $page->where('freeDays', 7));
    $this->actingAs(User::factory()->create(['paid_at' => now()]))->get(route('stats'))->assertInertia(fn ($page) => $page->where('freeDays', null));
});

it('only ever shows your own numbers', function () {
    hit(User::factory()->create(), '2026-10-01 09:00', 'Someone else');

    $this->actingAs(User::factory()->create())->get(route('stats'))->assertInertia(fn ($page) => $page
        ->where('totalHits', 0)
        ->where('topRules', []));
});
