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
            ['name' => 'Take out the trash', 'days' => ['wednesday'], 'points' => 2],
            ['name' => 'Stretch your back', 'chance' => 0.34, 'points' => 1],
            ['name' => 'Say "I love you"', 'chance' => 0.143, 'points' => 3],
            ['name' => 'Water the plants', 'every_value' => 1, 'every_unit' => 'week', 'chance' => 0.5, 'points' => 2],
            ['name' => 'Send the invoices', 'day_of_month' => -1, 'points' => 5],
            ['name' => 'Eat some cake', 'days' => ['saturday'], 'reward_cost' => 50],
        ];

        $settings = collect($rules)->mapWithKeys(fn (array $rule) => [$rule['name'] => $user->todoSettings()->create($rule)]);
        $today = $user->today();

        // [days ago, rule or one-shot name, done?]
        $history = [
            [0, 'Take out the trash', false],
            [0, 'Stretch your back', false],
            [0, 'Say "I love you"', false],
            [0, 'Call the plumber', false],
            [0, 'Water the plants', true],
            [1, 'Stretch your back', false],
            [1, 'Reply to the landlord', false],
            [1, 'Say "I love you"', true],
            [2, 'Water the plants', false],
            [2, 'Stretch your back', true],
            [4, 'Book the dentist', false],
            [4, 'Take out the trash', true],
            [5, 'Stretch your back', true],
            [6, 'Say "I love you"', true],
            [8, 'Send the invoices', true],
            [9, 'Stretch your back', true],
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

        $user->todos()->create(['name' => 'Buy birthday gift', 'date' => $today->addDays(2)->toDateString()]);
    }
}
