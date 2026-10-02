<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo data only: production has nothing to seed (the entrypoint
 * creates the first admin through `fleche:install`).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create(['name' => 'Clement', 'email' => 'test@example.com', 'points' => 35, 'timezone' => 'Europe/Brussels']);
        $user->forceFill(['is_admin' => true])->save();

        $rules = [
            ['name' => 'Bins out tonight 🗑️', 'days' => ['tuesday'], 'points' => 1],
            ['name' => 'Plank for 60 seconds', 'chance' => 1 / 3, 'points' => 3],
            ['name' => 'Text a friend you haven\'t heard from in a while', 'chance' => 1 / 7, 'points' => 2],
            ['name' => 'Water the plants 🪴', 'days' => ['sunday'], 'points' => 1],
            ['name' => 'Send this month\'s invoices', 'day_of_month' => -1, 'points' => 5],
            ['name' => 'Swap the toothbrush head', 'every_value' => 3, 'every_unit' => 'month', 'points' => 1],
            ['name' => 'Pizza night 🍕', 'days' => ['friday'], 'reward_cost' => 60],
        ];

        $settings = collect($rules)->mapWithKeys(fn (array $rule) => [$rule['name'] => $user->todoSettings()->create($rule)]);
        $today = $user->today();

        // [days ago, rule or one-shot name, done?]
        $history = [
            [0, 'Bins out tonight 🗑️', false],
            [0, 'Plank for 60 seconds', false],
            [0, 'Text a friend you haven\'t heard from in a while', false],
            [0, 'Book a haircut', false],
            [0, 'Water the plants 🪴', true],
            [1, 'Plank for 60 seconds', false],
            [1, 'Renew the car insurance', false],
            [1, 'Swap the toothbrush head', true],
            [2, 'Water the plants 🪴', false],
            [2, 'Plank for 60 seconds', true],
            [4, 'Return the library books', false],
            [4, 'Bins out tonight 🗑️', true],
            [5, 'Plank for 60 seconds', true],
            [6, 'Text a friend you haven\'t heard from in a while', true],
            [8, 'Send this month\'s invoices', true],
            [9, 'Plank for 60 seconds', true],
        ];

        foreach ($history as [$daysAgo, $name, $done]) {
            $setting = $settings->get($name);
            $date = $today->subDays($daysAgo);

            $todo = $user->todos()->create([
                'todo_setting_id' => $setting?->id,
                'name' => $name,
                'date' => $date->toDateString(),
                'points' => $setting->points ?? 1,
            ]);

            if ($done) {
                $todo->forceFill(['done_at' => $date->setTime(18, 0)])->save();
            }
        }

        $user->todos()->create(['name' => 'Buy a birthday gift for Sam 🎂', 'date' => $today->addDays(2)->toDateString()]);

        $this->call(HubPackSeeder::class);
    }
}
