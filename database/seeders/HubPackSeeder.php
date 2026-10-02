<?php

namespace Database\Seeders;

use App\Enums\HubPackStatus;
use App\Models\HubPack;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter packs for the community hub, published by "Fleche". Each one is a
 * small showcase of what rules can do (weekdays, intervals, end of month,
 * quarters, chance, rewards), so a newcomer gets it at a glance.
 *
 * Safe to re-run: packs are matched by name. Run it on the cloud instance with
 * `php artisan db:seed --class=HubPackSeeder`.
 */
class HubPackSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->firstOrCreate(
            ['email' => 'packs@fleche.io'],
            ['name' => 'Fleche', 'password' => Str::random(64)],
        );

        foreach ($this->packs() as $pack) {
            HubPack::query()->updateOrCreate(
                ['user_id' => $author->id, 'name' => $pack['name']],
                ['description' => $pack['description'], 'rules' => $pack['rules'], 'status' => HubPackStatus::Approved],
            );
        }
    }

    /**
     * @return array<int, array{name: string, description: string, rules: array<int, array<string, mixed>>}>
     */
    private function packs(): array
    {
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        return [
            [
                'name' => 'Desk Survival Kit 🪑',
                'description' => 'Tiny moves for people who sit all day. A few random ones pop up each workday, never the same mix twice.',
                'rules' => [
                    ['name' => 'Stand up and stretch for 2 minutes', 'days' => $weekdays, 'chance' => 3 / 4, 'points' => 1],
                    ['name' => 'Neck rolls, slowly', 'days' => $weekdays, 'chance' => 0.5, 'points' => 1],
                    ['name' => 'Wrist circles', 'days' => $weekdays, 'chance' => 1 / 3, 'points' => 1],
                    ['name' => '20 squats next to your desk', 'days' => $weekdays, 'chance' => 0.25, 'points' => 2],
                    ['name' => 'Lunch walk around the block', 'days' => $weekdays, 'chance' => 0.5, 'points' => 2],
                ],
            ],
            [
                'name' => 'Surprise Workout 🎲',
                'description' => 'You never know what today holds. Push-up day? Plank day? The dice decide, you just show up.',
                'rules' => [
                    ['name' => '15 push-ups', 'chance' => 1 / 3, 'points' => 3],
                    ['name' => 'Plank for 60 seconds', 'chance' => 1 / 3, 'points' => 3],
                    ['name' => '30 lunges', 'chance' => 0.25, 'points' => 3],
                    ['name' => '10 burpees (sorry)', 'chance' => 1 / 7, 'points' => 5],
                    ['name' => 'Go for a run', 'days' => ['saturday', 'sunday'], 'random_day' => true, 'every_value' => 1, 'every_unit' => 'week', 'points' => 5],
                ],
            ],
            [
                'name' => 'Freelancer Money Admin 💸',
                'description' => 'Invoices out, receipts in, taxes never forgotten. The boring money stuff, on autopilot.',
                'rules' => [
                    ['name' => 'Send this month\'s invoices', 'day_of_month' => -1, 'points' => 5],
                    ['name' => 'Snap and file this week\'s receipts', 'days' => ['friday'], 'points' => 2],
                    ['name' => 'Chase unpaid invoices', 'every_value' => 2, 'every_unit' => 'week', 'points' => 3],
                    ['name' => 'Prepare quarterly tax return', 'day_of_month' => 1, 'months' => [1, 4, 7, 10], 'points' => 10],
                    ['name' => 'Back up the accounting files', 'day_of_month' => 15, 'points' => 2],
                ],
            ],
            [
                'name' => 'Home on Autopilot 🏠',
                'description' => 'All the chores you only remember when it\'s too late. Now they remember for you.',
                'rules' => [
                    ['name' => 'Bins out tonight', 'days' => ['tuesday'], 'points' => 1],
                    ['name' => 'Change the bed sheets', 'days' => ['saturday', 'sunday'], 'random_day' => true, 'every_value' => 1, 'every_unit' => 'week', 'points' => 2],
                    ['name' => 'Descale the coffee machine', 'every_value' => 1, 'every_unit' => 'month', 'points' => 2],
                    ['name' => 'Swap the toothbrush head', 'every_value' => 3, 'every_unit' => 'month', 'points' => 1],
                    ['name' => 'Test the smoke alarms', 'every_value' => 6, 'every_unit' => 'month', 'points' => 3],
                ],
            ],
            [
                'name' => 'Plant Parent 🪴',
                'description' => 'Water, rotate, feed. Your monstera will finally stop judging you.',
                'rules' => [
                    ['name' => 'Water the plants', 'days' => ['sunday'], 'points' => 1],
                    ['name' => 'Mist the ferns', 'chance' => 1 / 3, 'points' => 1],
                    ['name' => 'Rotate the pots toward the light', 'every_value' => 2, 'every_unit' => 'week', 'points' => 1],
                    ['name' => 'Feed with fertilizer', 'day_of_month' => 1, 'months' => [3, 4, 5, 6, 7, 8, 9], 'points' => 2],
                ],
            ],
            [
                'name' => 'Stay in Touch 💬',
                'description' => 'Friendships need watering too. A gentle nudge, every now and then, never on a schedule.',
                'rules' => [
                    ['name' => 'Text a friend you haven\'t heard from in a while', 'chance' => 1 / 7, 'points' => 2],
                    ['name' => 'Call your family', 'days' => ['sunday'], 'points' => 2],
                    ['name' => 'Check this month\'s birthdays', 'day_of_month' => 1, 'points' => 1],
                    ['name' => 'Plan a coffee with someone', 'every_value' => 1, 'every_unit' => 'month', 'chance' => 0.5, 'points' => 3],
                ],
            ],
            [
                'name' => 'Treat Yourself 🎁',
                'description' => 'Rewards you buy with the points you earned. They only show up when you can afford them.',
                'rules' => [
                    ['name' => 'Pizza night 🍕', 'days' => ['friday'], 'reward_cost' => 60],
                    ['name' => 'Guilt-free gaming evening 🎮', 'chance' => 1 / 5, 'reward_cost' => 40],
                    ['name' => 'Lazy Sunday morning, no alarm', 'days' => ['sunday'], 'reward_cost' => 80],
                ],
            ],
        ];
    }
}
