<?php

namespace App\Actions;

use App\Models\TodoSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GenerateTodos
{
    /**
     * Runs every active rule for `$day`, optionally only for the given users.
     * Safe to re-run: a rule that already produced a todo that day is skipped.
     *
     * @param  array<int, int>|null  $userIds
     * @return int todos created
     */
    public function handle(CarbonImmutable $day, ?array $userIds = null): int
    {
        $created = 0;

        TodoSetting::query()
            ->where('active', true)
            ->when($userIds !== null, fn ($query) => $query->whereIn('user_id', $userIds))
            ->lazyById()
            ->each(function (TodoSetting $setting) use ($day, &$created): void {
                if ($setting->isDueOn($day) && $this->create($setting, $day)) {
                    $created++;
                }
            });

        return $created;
    }

    private function create(TodoSetting $setting, CarbonImmutable $day): bool
    {
        return DB::transaction(function () use ($setting, $day): bool {
            // A reward that cannot be paid for simply does not happen today.
            // Conditional decrement: the balance never goes below zero.
            if ($setting->isReward() && User::query()
                ->whereKey($setting->user_id)
                ->where('points', '>=', $setting->reward_cost)
                ->decrement('points', $setting->reward_cost) === 0) {
                return false;
            }

            $setting->todos()->create([
                'user_id' => $setting->user_id,
                'name' => $setting->name,
                'description' => $setting->description,
                'image' => $setting->image,
                'date' => $day->toDateString(),
                'points' => $setting->points,
            ]);

            return true;
        });
    }
}
