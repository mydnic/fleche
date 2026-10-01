<?php

namespace App\Console\Commands;

use App\Models\Todo;
use Illuminate\Console\Command;

/**
 * Cloud free tier keeps a few days of history; self-hosted and paid keep it all.
 */
class PruneCommand extends Command
{
    protected $signature = 'fleche:prune';

    protected $description = 'Cloud free tier: drop todos older than the retention window';

    // Database costs a lot, this is my way of cleaning out a bit
    public function handle(): int
    {
        if (config('fleche.edition') !== 'cloud') {
            return self::SUCCESS;
        }

        $deleted = Todo::query()
            ->whereDate('date', '<', today()->subDays(config('fleche.cloud.free_retention_days')))
            ->whereHas('user', fn ($query) => $query->whereNull('paid_at'))
            ->delete();

        $this->info("{$deleted} todos pruned");

        return self::SUCCESS;
    }
}
