<?php

namespace App\Http\Controllers;

use App\Actions\DuplicateRule;
use App\Models\TodoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Copies a rule and opens the copy's own form, where renaming or pausing it
 * is one field away.
 */
class DuplicateRuleController extends Controller
{
    public function __invoke(Request $request, TodoSetting $rule, DuplicateRule $duplicate): RedirectResponse
    {
        abort_unless($rule->user_id === $request->user()->id, 404);

        return to_route('rules.edit', $duplicate->handle($rule));
    }
}
