<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoSettingRequest;
use App\Models\TodoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        abort_unless($rule->user_id === $request->user()->id, 404);

        return Inertia::render('rules/Form', ['rule' => $rule]);
    }

    public function update(TodoSettingRequest $request, TodoSetting $rule): RedirectResponse
    {
        abort_unless($rule->user_id === $request->user()->id, 404);

        $rule->update($request->payload());

        return $request->isMethod('PATCH') ? back() : to_route('rules.index');
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
        abort_unless($rule->user_id === $request->user()->id, 404);

        $rule->delete();

        return to_route('rules.index');
    }
}
