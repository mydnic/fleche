<?php

namespace App\Actions;

use App\Models\TodoSetting;
use Illuminate\Support\Str;

/**
 * Copies a rule for its own owner, todos excluded, so history and stats stay
 * clean. Everything else is copied as is, `active` included: a paused rule
 * gives a paused copy. The `image` column travels as a path, no file work: a
 * todo already shares its rule's file, so sharing it with a copy is the same
 * deal.
 */
class DuplicateRule
{
    public function handle(TodoSetting $rule): TodoSetting
    {
        $copy = $rule->replicate();
        $copy->name = $this->copyName($rule);
        $copy->save();

        return $copy;
    }

    /**
     * "X (copy)", then "X (copy 2)" and up until the owner has no such rule
     * yet. The suffix eats into the name: `name` stops at 255 characters.
     */
    private function copyName(TodoSetting $rule): string
    {
        $taken = TodoSetting::query()->where('user_id', $rule->user_id)->pluck('name')->all();
        $i = 1;

        do {
            $suffix = $i === 1 ? ' (copy)' : " (copy {$i})";
            $candidate = Str::limit($rule->name, 255 - strlen($suffix), '').$suffix;
            $i++;
        } while (in_array($candidate, $taken, true));

        return $candidate;
    }
}
