<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoSettingRequest;
use App\Models\TodoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TodoSettings, called "rules" in the UI.
 */
class RuleController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('rules/Index', [
            'rules' => $request->user()->todoSettings()->orderByDesc('active')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('rules/Form', ['rule' => null]);
    }

    public function store(TodoSettingRequest $request): RedirectResponse
    {
        $request->user()->todoSettings()->create($request->payload());

        return to_route('rules.index');
    }

    public function edit(Request $request, TodoSetting $rule): Response
    {
        $this->authorizeRule($request, $rule);

        return Inertia::render('rules/Form', ['rule' => $rule]);
    }

    public function update(TodoSettingRequest $request, TodoSetting $rule): RedirectResponse
    {
        $this->authorizeRule($request, $rule);

        $rule->update($request->payload());

        return $request->isMethod('PATCH') ? back() : to_route('rules.index');
    }

    /**
     * Copies a rule, todos excluded, and opens the copy's form. The copy stays
     * active: pausing it is one switch away on the form it lands on.
     */
    public function duplicate(Request $request, TodoSetting $rule): RedirectResponse
    {
        $this->authorizeRule($request, $rule);

        $copy = $rule->replicate();
        $copy->name = $this->copyName($request, $rule->name);
        $copy->save();

        return to_route('rules.edit', $copy);
    }

    /**
     * "X (copy)", then "X (copy 2)" and up until the user has no such rule
     * yet. The suffix eats into the name: `name` stops at 255 characters.
     */
    private function copyName(Request $request, string $name): string
    {
        $taken = $request->user()->todoSettings()->pluck('name')->all();
        $i = 1;

        do {
            $suffix = $i === 1 ? ' (copy)' : " (copy {$i})";
            $candidate = Str::limit($name, 255 - strlen($suffix), '').$suffix;
            $i++;
        } while (in_array($candidate, $taken, true));

        return $candidate;
    }

    /**
     * Renames a group across all its rules; a null `to` dissolves it.
     */
    public function group(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from' => ['required', 'string', 'max:255'],
            'to' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->todoSettings()->where('group', $data['from'])->update(['group' => $data['to']]);

        return back();
    }

    public function destroy(Request $request, TodoSetting $rule): RedirectResponse
    {
        $this->authorizeRule($request, $rule);

        $rule->delete();

        return to_route('rules.index');
    }

    private function authorizeRule(Request $request, TodoSetting $rule): void
    {
        abort_unless($rule->user_id === $request->user()->id, 404);
    }
}
