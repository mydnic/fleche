<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_admin
 * @property int $points
 * @property Carbon|null $paid_at
 * @property bool $notify_mail
 * @property bool $notify_telegram
 * @property int $day_start_hour
 * @property string $timezone
 * @property string|null $telegram_chat_id
 * @property string|null $telegram_link_token
 */
#[Fillable(['name', 'email', 'password', 'notify_mail', 'notify_telegram', 'day_start_hour', 'timezone'])]
#[Hidden(['password', 'remember_token', 'telegram_link_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'points' => 0,
        'notify_mail' => true,
        'notify_telegram' => false,
        'day_start_hour' => 7,
        'timezone' => 'UTC',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'paid_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'notify_mail' => 'boolean',
            'notify_telegram' => 'boolean',
        ];
    }

    /** @return HasMany<TodoSetting, $this> */
    public function todoSettings(): HasMany
    {
        return $this->hasMany(TodoSetting::class);
    }

    /** @return HasMany<Todo, $this> */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    /**
     * Cloud free tier keeps 7 days of history; self-hosted and paid keep it all.
     */
    public function hasUnlimitedHistory(): bool
    {
        return config('fleche.edition') !== 'cloud' || $this->paid_at !== null;
    }

    /** The user's calendar day, in their own timezone. */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone)->startOfDay();
    }

    public function routeNotificationForTelegram(): ?string
    {
        return $this->telegram_chat_id;
    }
}
