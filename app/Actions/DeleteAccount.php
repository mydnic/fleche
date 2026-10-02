<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR erasure, self-service: the account and everything it owns. Todos,
 * rules and hub authorship go with the row (cascade / null on delete); API
 * tokens and pictures need to be removed by hand.
 */
class DeleteAccount
{
    public function handle(User $user): void
    {
        $images = $user->todoSettings()->whereNotNull('image')->pluck('image')
            ->merge($user->todos()->whereNotNull('image')->pluck('image'))
            ->unique()
            ->all();

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        Storage::delete($images);
    }
}
