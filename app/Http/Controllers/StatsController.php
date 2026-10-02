<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    private const DAYS = 90;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $today = $user->today();
        $hitDays = $this->hitDays($user);
        [$current, $best] = $this->streaks($hitDays->keys()->all(), $today);

        return Inertia::render('Stats', [
            'days' => $this->series($hitDays, $today),
            'currentStreak' => $current,
            'bestStreak' => $best,
            'totalHits' => $hitDays->sum(),
            'points' => $user->points,
            'hitRate' => $this->hitRate($user, $today),
            'topRules' => $this->topRules($user),
            // Free cloud history is wiped after this many days: the chart shows
            // the cut so it's obvious what unlocking keeps.
            'freeDays' => $user->hasUnlimitedHistory() ? null : config('fleche.cloud.free_retention_days'),
        ]);
    }

    /**
     * Done todos per local day (the user's timezone), oldest first.
     *
     * @return Collection<string, int>
     */
    private function hitDays(User $user): Collection
    {
        return $user->todos()->done()->pluck('done_at')
            ->countBy(fn (CarbonImmutable $doneAt): string => $doneAt->setTimezone($user->timezone)->toDateString())
            ->sortKeys();
    }

    /**
     * @param  Collection<string, int>  $hitDays
     * @return array<int, array{date: string, hits: int}>
     */
    private function series(Collection $hitDays, CarbonImmutable $today): array
    {
        return collect(range(self::DAYS - 1, 0))
            ->map(fn (int $daysAgo): string => $today->subDays($daysAgo)->toDateString())
            ->map(fn (string $date): array => ['date' => $date, 'hits' => $hitDays->get($date, 0)])
            ->all();
    }

    /**
     * Consecutive days with at least one hit. Today without a hit (yet) doesn't
     * break the current streak: the day isn't over.
     *
     * @param  array<int, string>  $dates  sorted ascending
     * @return array{0: int, 1: int}
     */
    private function streaks(array $dates, CarbonImmutable $today): array
    {
        $best = $run = 0;
        $previous = null;

        foreach ($dates as $date) {
            $run = $previous !== null && CarbonImmutable::parse($previous)->addDay()->toDateString() === $date ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $date;
        }

        $last = end($dates);
        $alive = $last === $today->toDateString() || $last === $today->subDay()->toDateString();

        return [$alive ? $run : 0, $best];
    }

    /** Share of the last 30 days' todos that got done, 0..100, null without todos. */
    private function hitRate(User $user, CarbonImmutable $today): ?int
    {
        $todos = $user->todos()
            ->whereDate('date', '>', $today->subDays(30)->toDateString())
            ->whereDate('date', '<=', $today->toDateString());

        $total = (clone $todos)->count();

        return $total === 0 ? null : (int) round((clone $todos)->done()->count() / $total * 100);
    }

    /**
     * @return array<int, array{name: string, hits: int}>
     */
    private function topRules(User $user): array
    {
        return $user->todos()->done()
            ->select('name', DB::raw('count(*) as hits'))
            ->groupBy('name')
            ->orderByDesc('hits')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => ['name' => $row->name, 'hits' => (int) $row->hits])
            ->all();
    }
}
