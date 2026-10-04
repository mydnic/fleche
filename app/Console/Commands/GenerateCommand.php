<?php

namespace App\Console\Commands;

use App\Actions\GenerateTodos;
use App\Models\User;
use App\Notifications\DailyTodos;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * A user's day starts at their chosen hour, in their timezone: that is when the
 * dice are rolled (once a day, never every hour) and the list is sent right
 * after. Run hourly, every timezone, half-hour offsets included, passes each
 * local hour exactly once a day.
 */
class GenerateCommand extends Command
{
    protected $signature = 'fleche:generate {--date= : Only run every rule for this date, no notification}';

    protected $description = 'Start the day of users whose chosen hour it is: roll their rules, send their list';

    public function handle(GenerateTodos $generate): int
    {
        if ($this->option('date')) {
            $day = CarbonImmutable::parse($this->option('date'))->startOfDay();
            $this->info($generate->handle($day).' todos created for '.$day->toDateString());

            return self::SUCCESS;
        }

        foreach (User::query()->distinct()->pluck('timezone') as $timezone) {
            $local = CarbonImmutable::now($timezone);

            User::query()
                ->where('timezone', $timezone)
                ->where('day_start_hour', $local->hour)
                ->chunkById(200, fn (Collection $users) => $this->startDay($generate, $users, $local));
        }

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function startDay(GenerateTodos $generate, Collection $users, CarbonImmutable $local): void
    {
        $created = $generate->handle($local->startOfDay(), $users->modelKeys());
        $this->info("{$created} todos created for {$users->count()} users in {$local->timezoneName}");

        foreach ($users as $user) {
            $todos = $user->todos()->open()->whereDate('date', $local->toDateString())->get();

            if ($todos->isNotEmpty()) {
                $user->notify(new DailyTodos($todos));
            }
        }
    }
}
