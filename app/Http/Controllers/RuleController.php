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
        $this->authorizeRule($request, $rule);

        return Inertia::render('rules/Form', ['rule' => $rule]);
    }

    public function update(TodoSettingRequest $request, TodoSetting $rule): RedirectResponse
    {
        $this->authorizeRule($request, $rule);

        $rule->update($request->payload());

        return $request->isMethod('PATCH') ? back() : to_route('rules.index');
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
