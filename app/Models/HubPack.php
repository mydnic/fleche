<?php

namespace App\Models;

use App\Enums\HubPackStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A rule or pack of rules shared on the community hub (cloud only).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property array<int, array<string, mixed>> $rules
 * @property HubPackStatus $status
 * @property int $imports_count
 * @property-read User $user
 */
#[Fillable(['user_id', 'name', 'description', 'rules', 'status'])]
class HubPack extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'status' => HubPackStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
