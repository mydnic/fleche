<?php

use App\Models\Todo;
use App\Models\TodoSetting;
use App\Models\User;
use App\Notifications\DailyTodos;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Telegram\TelegramChannel;

it('starts the day at the user\'s hour: rolls the rules, then sends the list everywhere', function () {
    Notification::fake();

    // 07:00 in Brussels (summer time) is 05:00 UTC.
    $this->travelTo(CarbonImmutable::parse('2026-07-01 05:02', 'UTC'));

    $user = User::factory()->create(['day_start_hour' => 7, 'notify_telegram' => true, 'timezone' => 'Europe/Brussels']);
    $user->forceFill(['telegram_chat_id' => '42'])->save();
    Todo::factory()->for($user)->create(['name' => 'Yesterday', 'date' => '2026-06-30']);
    Todo::factory()->for($user)->create(['name' => 'Everywhere']);
    TodoSetting::factory()->for($user)->create(['name' => 'Daily stretch']);
    Todo::factory()->for($user)->done()->create(['name' => 'Done already']);
    User::factory()->create(['day_start_hour' => 7]); // UTC: it's 05:00 there

    $this->artisan('fleche:generate');

    Notification::assertSentTo($user, DailyTodos::class, function (DailyTodos $notification) use ($user) {
        expect($notification->via($user))->toBe(['mail', TelegramChannel::class])
            ->and($notification->toTelegram($user)->toArray()['text'])->toContain('Everywhere')->not->toContain('Yesterday')->not->toContain('Done already');

        // The every-day rule had just rolled: its todo was generated, then sent.
        return $notification->todos->pluck('name')->sort()->values()->all() === ['Daily stretch', 'Everywhere'];
    });
    Notification::assertCount(1);
});
