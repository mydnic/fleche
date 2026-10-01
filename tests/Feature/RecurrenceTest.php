<?php

use App\Actions\GenerateTodos;
use App\Models\TodoSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Lottery;

// Fixed outcomes must never leak into the next test, even a failing one.
afterEach(fn () => Lottery::determineResultsNormally());

// 2026-10-07 is a Wednesday.
$wednesday = CarbonImmutable::parse('2026-10-07');

function rule(array $attributes = []): TodoSetting
{
    return TodoSetting::factory()->create($attributes);
}

it('fires every day with no rule set', function () use ($wednesday) {
    expect(rule()->isDueOn($wednesday))->toBeTrue();
});

it('respects weekdays', function () use ($wednesday) {
    expect(rule(['days' => ['wednesday']])->isDueOn($wednesday))->toBeTrue()
        ->and(rule(['days' => ['monday', 'friday']])->isDueOn($wednesday))->toBeFalse();
});

it('respects day of month, including the last one', function () {
    expect(rule(['day_of_month' => 7])->isDueOn(CarbonImmutable::parse('2026-10-07')))->toBeTrue()
        ->and(rule(['day_of_month' => 8])->isDueOn(CarbonImmutable::parse('2026-10-07')))->toBeFalse()
        ->and(rule(['day_of_month' => -1])->isDueOn(CarbonImmutable::parse('2026-02-28')))->toBeTrue()
        ->and(rule(['day_of_month' => -1])->isDueOn(CarbonImmutable::parse('2026-02-27')))->toBeFalse();
});

it('respects months (first day of each quarter)', function () {
    $quarterly = ['day_of_month' => 1, 'months' => [1, 4, 7, 10]];

    expect(rule($quarterly)->isDueOn(CarbonImmutable::parse('2026-10-01')))->toBeTrue()
        ->and(rule($quarterly)->isDueOn(CarbonImmutable::parse('2026-11-01')))->toBeFalse();
});

it('respects a start date', function () use ($wednesday) {
    expect(rule(['start_after' => '2026-10-08'])->isDueOn($wednesday))->toBeFalse()
        ->and(rule(['start_after' => '2026-10-07'])->isDueOn($wednesday))->toBeTrue();
});

it('waits the interval since the last time it was done', function () use ($wednesday) {
    $rule = rule(['every_value' => 2, 'every_unit' => 'week']);
    $rule->todos()->create(['user_id' => $rule->user_id, 'name' => 'x', 'date' => '2026-09-30'])->markDone();

    expect($rule->isDueOn($wednesday))->toBeFalse()
        ->and($rule->isDueOn($wednesday->addWeeks(2)))->toBeTrue();
});

it('does not stack unless duplicates are allowed', function () use ($wednesday) {
    $single = rule();
    $single->todos()->create(['user_id' => $single->user_id, 'name' => 'x', 'date' => '2026-10-06']);

    $stacking = rule(['allow_duplicates' => true]);
    $stacking->todos()->create(['user_id' => $stacking->user_id, 'name' => 'x', 'date' => '2026-10-06']);

    expect($single->isDueOn($wednesday))->toBeFalse()
        ->and($stacking->isDueOn($wednesday))->toBeTrue();
});

it('never fires an inactive rule', function () use ($wednesday) {
    expect(rule(['active' => false])->isDueOn($wednesday))->toBeFalse();
});

it('rolls the dice when the calendar agrees', function () use ($wednesday) {
    $rule = rule(['chance' => 0.5, 'days' => ['wednesday']]);

    Lottery::alwaysWin();
    expect($rule->isDueOn($wednesday))->toBeTrue()
        ->and($rule->isDueOn($wednesday->addDay()))->toBeFalse(); // wrong day: no luck helps

    Lottery::alwaysLose();
    expect($rule->isDueOn($wednesday))->toBeFalse();
});

it('generates once per day even when run twice', function () use ($wednesday) {
    rule(['allow_duplicates' => true]);

    expect(app(GenerateTodos::class)->handle($wednesday))->toBe(1)
        ->and(app(GenerateTodos::class)->handle($wednesday))->toBe(0);
});

it('only generates a reward the user can afford, and charges it', function () use ($wednesday) {
    $user = User::factory()->create(['points' => 40]);
    rule(['user_id' => $user->id, 'reward_cost' => 50, 'name' => 'Cake']);

    expect(app(GenerateTodos::class)->handle($wednesday))->toBe(0)
        ->and($user->refresh()->points)->toBe(40);

    $user->forceFill(['points' => 60])->save();

    expect(app(GenerateTodos::class)->handle($wednesday->addDay()))->toBe(1)
        ->and($user->refresh()->points)->toBe(10)
        ->and($user->todos()->sole()->name)->toBe('Cake');
});

it('fires on only one of the days when the day is drawn at random', function () use ($wednesday) {
    $rule = rule(['days' => ['monday', 'wednesday'], 'random_day' => true, 'allow_duplicates' => true]);
    $hits = collect(range(1, 400))->filter(fn () => $rule->isDueOn($wednesday))->count();

    // Wednesday is drawn about half the time; a day outside the list never fires.
    expect($hits)->toBeGreaterThan(140)->toBeLessThan(260)
        ->and($rule->isDueOn($wednesday->addDay()))->toBeFalse();
});

it('skips day 31 in a 30-day month, but "last day" still fires', function () {
    expect(rule(['day_of_month' => 31])->isDueOn(CarbonImmutable::parse('2026-09-30')))->toBeFalse()
        ->and(rule(['day_of_month' => -1])->isDueOn(CarbonImmutable::parse('2026-09-30')))->toBeTrue();
});

it('counts the interval in whole units, from the day it was done', function (string $unit, string $doneOn, bool $due) use ($wednesday) {
    $rule = rule(['every_value' => 1, 'every_unit' => $unit]);
    $rule->todos()->create(['user_id' => $rule->user_id, 'name' => 'x', 'date' => $doneOn])->markDone();

    expect($rule->isDueOn($wednesday))->toBe($due);
})->with([
    'week, exactly a week ago' => ['week', '2026-09-30', true],
    'week, six days ago' => ['week', '2026-10-01', false],
    'month, exactly a month ago' => ['month', '2026-09-07', true],
    'month, a day short' => ['month', '2026-09-08', false],
    'year, exactly a year ago' => ['year', '2025-10-07', true],
    'year, a day short' => ['year', '2025-10-08', false],
    'day, yesterday' => ['day', '2026-10-06', true],
]);

it('only counts done todos for the interval, not open ones', function () use ($wednesday) {
    $rule = rule(['every_value' => 2, 'every_unit' => 'week', 'allow_duplicates' => true]);
    $rule->todos()->create(['user_id' => $rule->user_id, 'name' => 'x', 'date' => '2026-10-06']);

    expect($rule->isDueOn($wednesday))->toBeTrue();
});

it('never generates twice the same day, done or not', function () use ($wednesday) {
    $rule = rule(['allow_duplicates' => true]);
    $rule->todos()->create(['user_id' => $rule->user_id, 'name' => 'x', 'date' => $wednesday->toDateString()])->markDone();

    expect($rule->isDueOn($wednesday))->toBeFalse()
        ->and($rule->isDueOn($wednesday->addDay()))->toBeTrue();
});

it('needs every set filter to agree', function () {
    // First Wednesday of a quarter month, on or after the start date.
    $rule = rule(['days' => ['wednesday'], 'months' => [1, 4, 7, 10], 'day_of_month' => 7, 'start_after' => '2026-01-01']);

    expect($rule->isDueOn(CarbonImmutable::parse('2026-10-07')))->toBeTrue() // Wed 7 Oct
        ->and($rule->isDueOn(CarbonImmutable::parse('2027-04-07')))->toBeTrue() // Wed 7 Apr
        ->and($rule->isDueOn(CarbonImmutable::parse('2026-01-07')))->toBeTrue() // Wed 7 Jan
        ->and($rule->isDueOn(CarbonImmutable::parse('2025-10-07')))->toBeFalse() // before start
        ->and($rule->isDueOn(CarbonImmutable::parse('2026-11-07')))->toBeFalse() // wrong month (Sat)
        ->and($rule->isDueOn(CarbonImmutable::parse('2026-07-07')))->toBeFalse(); // Tue 7 Jul
});

it('never rolls for a certain rule', function () use ($wednesday) {
    Lottery::alwaysLose();

    expect(rule(['chance' => 1])->isDueOn($wednesday))->toBeTrue();
});

it('does not overflow months: a month before March 31st is February 28th', function () {
    $rule = rule(['every_value' => 1, 'every_unit' => 'month']);
    // 29 days before: less than a month, must still block.
    $rule->todos()->create(['user_id' => $rule->user_id, 'name' => 'x', 'date' => '2026-03-02'])->markDone();

    expect($rule->isDueOn(CarbonImmutable::parse('2026-03-31')))->toBeFalse();
});
