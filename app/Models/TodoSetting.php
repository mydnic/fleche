<?php

namespace App\Models;

use App\Enums\EveryUnit;
use App\Support\Images;
use Carbon\CarbonImmutable;
use Database\Factories\TodoSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Lottery;

/**
 * A programmable task: the rules deciding, each day, whether a Todo appears.
 *
 * Every rule is a filter; a day passes only if all the set ones agree. Same
 * semantics as the original `TodoPlanner` trait, stored as data instead of code.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $group free-form label the rules page sorts rules under
 * @property string|null $description
 * @property string|null $image
 * @property bool $active
 * @property array<int, string>|null $days lowercase english weekday names
 * @property bool $random_day only one of `days`, drawn each day
 * @property int|null $every_value
 * @property EveryUnit|null $every_unit
 * @property int|null $day_of_month 1..31, or -1 for the last day of the month
 * @property array<int, int>|null $months 1..12
 * @property Carbon|null $start_after
 * @property float $chance 0..1
 * @property bool $allow_duplicates
 * @property int $points earned when the todo is done
 * @property int|null $reward_cost set = reward rule, costs this many points
 * @property-read User $user
 */
#[Hidden(['image'])]
#[Appends(['image_url'])]
#[Fillable([
    'name', 'group', 'description', 'image', 'active', 'days', 'random_day', 'every_value', 'every_unit',
    'day_of_month', 'months', 'start_after', 'chance', 'allow_duplicates', 'points',
    'reward_cost',
    'user_id',
    'date',
])]
class TodoSetting extends Model
{
    /** @use HasFactory<TodoSettingFactory> */
    use HasFactory;

    public const WEEKDAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    /** Attributes a rule carries when shared on the hub. */
    public const SHAREABLE = [
        'name', 'description', 'days', 'random_day', 'every_value', 'every_unit',
        'day_of_month', 'months', 'start_after', 'chance', 'allow_duplicates', 'points', 'reward_cost',
    ];

    protected $attributes = [
        'active' => true,
        'chance' => 1,
        'points' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'days' => 'array',
            'random_day' => 'boolean',
            'every_value' => 'integer',
            'every_unit' => EveryUnit::class,
            'day_of_month' => 'integer',
            'months' => 'array',
            'start_after' => 'date:Y-m-d',
            'chance' => 'float',
            'allow_duplicates' => 'boolean',
            'points' => 'integer',
            'reward_cost' => 'integer',
        ];
    }

    /** @return Attribute<string|null, never> */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => app(Images::class)->url($this->image));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Todo, $this> */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function isReward(): bool
    {
        return $this->reward_cost !== null;
    }

    /**
     * A short human summary of a rule's attributes, e.g. "weekdays · 1 in 3
     * chance". Mirrors `describeRule()` in resources/js/lib/rule.ts, for the
     * Blade landing page.
     *
     * @param  array<string, mixed>  $rule
     */
    public static function describe(array $rule, bool $withChance = true): string
    {
        $days = $rule['days'] ?? [];
        $random = (bool) ($rule['random_day'] ?? false);
        $months = $rule['months'] ?? [];
        $sameSet = fn (array $a, array $b): bool => count($a) === count($b) && array_diff($b, $a) === [];
        $parts = [];

        if ($days !== []) {
            $short = fn (string $day): string => ucfirst(substr($day, 0, 3));
            $parts[] = match (true) {
                $sameSet($days, array_slice(self::WEEKDAYS, 0, 5)) => $random ? 'one weekday' : 'weekdays',
                $sameSet($days, ['saturday', 'sunday']) => $random ? 'Sat or Sun' : 'weekends',
                default => ($random ? 'one of ' : '').implode($random ? ' or ' : ', ', array_map($short, $days)),
            };
        }

        if (isset($rule['day_of_month'])) {
            $parts[] = $rule['day_of_month'] === -1 ? 'last day of the month' : 'on the '.date('jS', mktime(0, 0, 0, 1, $rule['day_of_month']));
        }

        if ($months !== []) {
            sort($months);
            $name = fn (int $month): string => date('M', mktime(0, 0, 0, $month, 1));
            $contiguous = count($months) > 2 && end($months) - $months[0] === count($months) - 1;

            $parts[] = match (true) {
                $sameSet($months, [1, 4, 7, 10]) => 'every quarter',
                $contiguous => $name($months[0]).'–'.$name(end($months)),
                default => 'in '.implode(', ', array_map($name, $months)),
            };
        }

        if (! empty($rule['every_value']) && ! empty($rule['every_unit'])) {
            $value = (int) $rule['every_value'];
            $parts[] = 'at most every '.($value === 1 ? $rule['every_unit'] : "{$value} {$rule['every_unit']}s");
        }

        if ($withChance && ($rule['chance'] ?? 1) < 1) {
            $parts[] = self::describeChance($rule['chance']).' chance';
        }

        return $parts === [] ? 'every day' : implode(' · ', $parts);
    }

    /**
     * "1 in 3" when the odds are a clean fraction, "75%" otherwise. Mirrors
     * `describeChance()` in resources/js/lib/rule.ts.
     */
    public static function describeChance(float $chance): string
    {
        $oneIn = 1 / $chance;

        return abs($oneIn - round($oneIn)) / $oneIn < 0.02 ? '1 in '.round($oneIn) : round($chance * 100).'%';
    }

    /**
     * Whether the calendar rules and the dice let a todo appear on `$day`.
     * Does not look at points: that is the generator's job, atomically.
     */
    public function isDueOn(CarbonImmutable $day): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->start_after !== null && $day->lt($this->start_after->startOfDay())) {
            return false;
        }

        if ($this->day_of_month !== null && $day->day !== ($this->day_of_month === -1 ? $day->daysInMonth : $this->day_of_month)) {
            return false;
        }

        if (! empty($this->months) && ! in_array($day->month, $this->months)) {
            return false;
        }

        if (! empty($this->days)) {
            $days = $this->random_day ? [Arr::random($this->days)] : $this->days;

            if (! in_array(strtolower($day->englishDayOfWeek), $days, true)) {
                return false;
            }
        }

        if ($this->every_unit !== null && $this->every_value) {
            // NoOverflow: a month before March 31st is February 28th, not March 3rd.
            $since = match ($this->every_unit) {
                EveryUnit::Day => $day->subDays($this->every_value),
                EveryUnit::Week => $day->subWeeks($this->every_value),
                EveryUnit::Month => $day->subMonthsNoOverflow($this->every_value),
                EveryUnit::Year => $day->subYearsNoOverflow($this->every_value),
            };

            if ($this->todos()->done()->whereDate('date', '>', $since)->exists()) {
                return false;
            }
        }

        if (! $this->allow_duplicates && $this->todos()->open()->exists()) {
            return false;
        }

        // A day already generated is never generated twice, whatever re-runs.
        if ($this->todos()->whereDate('date', $day)->exists()) {
            return false;
        }

        // Lottery rather than mt_rand: tests can fix the outcome.
        return $this->chance >= 1 || Lottery::odds($this->chance)->choose();
    }
}
