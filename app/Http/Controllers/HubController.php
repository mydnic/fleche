<?php

namespace App\Http\Controllers;

use App\Enums\HubPackStatus;
use App\Models\HubPack;
use App\Models\TodoSetting;
use App\Support\Hub;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class HubController extends Controller
{
    public function __construct(private Hub $hub) {}

    public function index(Request $request): Response
    {
        $cloud = config('fleche.edition') === 'cloud';
        $user = $request->user();

        try {
            $packs = $this->hub->search($request->string('q')->toString() ?: null);
            $error = null;
        } catch (Throwable) {
            $packs = [];
            $error = 'The community hub is unreachable right now.';
        }

        return Inertia::render('Hub', [
            'packs' => $packs,
            'error' => $error,
            'q' => $request->string('q')->toString(),
            'canPublish' => $cloud,
            'rules' => $cloud ? $user->todoSettings()->orderBy('name')->get(['id', 'name']) : [],
            'mine' => $cloud ? HubPack::query()->whereBelongsTo($user)->latest()->get(['id', 'name', 'status', 'imports_count']) : [],
            'pending' => $cloud && $user->is_admin
                ? HubPack::query()->where('status', HubPackStatus::Pending)->with('user:id,name')->oldest()->get()->map(fn (HubPack $pack) => $this->hub->present($pack))
                : [],
        ]);
    }

    public function import(Request $request, int $pack): RedirectResponse
    {
        try {
            $count = $this->hub->importInto($request->user(), $this->hub->take($pack));
        } catch (RequestException) {
            return back()->with('status', 'Could not import this pack.');
        }

        return to_route('rules.index')->with('status', "{$count} rule(s) imported 🎯");
    }

    public function publish(Request $request): RedirectResponse
    {
        abort_unless(config('fleche.edition') === 'cloud', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'rule_ids' => ['required', 'array', 'min:1'],
            'rule_ids.*' => ['integer'],
        ]);

        $rules = $request->user()->todoSettings()->whereKey($data['rule_ids'])->get()
            ->map(fn (TodoSetting $rule): array => [...Arr::only($rule->toArray(), TodoSetting::SHAREABLE), 'image_url' => $rule->image_url])
            ->all();

        abort_if($rules === [], 422);

        HubPack::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'rules' => $rules,
        ]);

        return back()->with('status', 'Sent for review. It will appear once approved.');
    }

    public function moderate(Request $request, HubPack $pack): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $pack->update($request->validate([
            'status' => ['required', Rule::enum(HubPackStatus::class)->except(HubPackStatus::Pending)],
        ]));

        return back();
    }

    /**
     * Takes a pack off the hub for good. Rules already imported from it stay
     * in their owners' accounts: those are copies.
     */
    public function destroy(Request $request, HubPack $pack): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $pack->delete();

        return back()->with('status', "\"{$pack->name}\" removed from the hub.");
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(config('fleche.edition') === 'cloud' && $request->user()->is_admin, 403);
    }
}
