<?php

namespace App\Models;

use App\Support\Images;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $todo_setting_id
 * @property string $name
 * @property string|null $description
 * @property string|null $image
 * @property Carbon $date
 * @property Carbon|null $done_at
 * @property int $points
 * @property-read User $user
 * @property-read TodoSetting|null $todoSetting
 */
#[Fillable(['user_id', 'todo_setting_id', 'name', 'description', 'image', 'date', 'points'])]
#[Hidden(['image'])]
#[Appends(['image_url'])]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'done_at' => 'datetime',
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

    /** @return BelongsTo<TodoSetting, $this> */
    public function todoSetting(): BelongsTo
    {
        return $this->belongsTo(TodoSetting::class);
    }

    /** @param Builder<Todo> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('done_at');
    }

    /** @param Builder<Todo> $query */
    public function scopeDone(Builder $query): void
    {
        $query->whereNotNull('done_at');
    }

    /**
     * Final: there is no way back, in the UI or the API. The conditional
     * update makes a double click (or two clients) award the points once.
     */
    public function markDone(): void
    {
        DB::transaction(function (): void {
            $updated = static::query()->whereKey($this->id)->whereNull('done_at')->update(['done_at' => now()]);

            if ($updated === 1 && $this->points > 0) {
                User::query()->whereKey($this->user_id)->increment('points', $this->points);
            }
        });

        $this->refresh();
    }
}
