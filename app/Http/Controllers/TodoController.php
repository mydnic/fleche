<?php

namespace App\Http\Controllers;

use App\Http\Requests\TodoRequest;
use App\Models\Todo;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = $user->today()->toDateString();

        $doneOn = $request->validate([
            'done_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
        ])['done_on'] ?? $today;

        // `done_at` is in app time; a day runs from the user's midnight to the next.
        $doneFrom = CarbonImmutable::parse($doneOn, $user->timezone)->startOfDay()->setTimezone(config('app.timezone'));
        $todayFrom = $user->today()->setTimezone(config('app.timezone'));

        return Inertia::render('Today', [
            'today' => $today,
            'open' => $user->todos()->open()->whereDate('date', $today)->orderBy('id')->get(),
            // Still open from earlier days, newest day first; grouped by day on the page.
            'overdue' => $user->todos()->open()->whereDate('date', '<', $today)->orderByDesc('date')->orderBy('id')->get(),
            'upcoming' => $user->todos()->open()->whereDate('date', '>', $today)->orderBy('date')->get(),
            // What was checked off on the picked day (today by default).
            'doneOn' => $doneOn,
            'done' => $user->todos()->done()->where('done_at', '>=', $doneFrom)->where('done_at', '<', $doneFrom->addDay())->latest('done_at')->get(),
            'doneTodayCount' => $user->todos()->done()->where('done_at', '>=', $todayFrom)->count(),
            'hasDoneEver' => $user->todos()->done()->exists(),
        ]);
    }

    public function store(TodoRequest $request): RedirectResponse
    {
        $request->user()->todos()->create($request->payload());

        return back();
    }

    public function done(Request $request, Todo $todo): RedirectResponse
    {
        abort_unless($todo->user_id === $request->user()->id, 404);

        $todo->markDone();

        return back();
    }
}
