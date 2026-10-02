<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AuthorStatsSnapshot;
use Illuminate\Console\Command;

class SnapshotAuthorStatsCommand extends Command
{
    protected $signature = 'stats:snapshot
                            {--year= : Calendar year (defaults to current UTC month)}
                            {--month= : Calendar month 1-12}
                            {--user= : Limit to a single user id}';

    protected $description = 'Persist author and per-story monthly stats snapshots';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now('UTC')->year);
        $month = (int) ($this->option('month') ?: now('UTC')->month);

        if ($month < 1 || $month > 12) {
            $this->error('Month must be between 1 and 12.');

            return self::FAILURE;
        }

        $userId = $this->option('user');

        if ($userId) {
            $user = User::query()->find($userId);
            if (! $user) {
                $this->error('User not found.');

                return self::FAILURE;
            }

            AuthorStatsSnapshot::snapshotMonth($user, $year, $month);
            $this->info("Snapshotted {$year}-{$month} for user {$user->id}.");

            return self::SUCCESS;
        }

        $count = AuthorStatsSnapshot::snapshotAllAuthors($year, $month);
        $this->info("Snapshotted {$year}-{$month} for {$count} authors.");

        if (! $this->option('year') && ! $this->option('month') && now('UTC')->day === 1) {
            $previous = now('UTC')->subMonth();
            $prevCount = AuthorStatsSnapshot::snapshotAllAuthors($previous->year, $previous->month);
            $this->info("Snapshotted {$previous->year}-{$previous->month} for {$prevCount} authors.");
        }

        return self::SUCCESS;
    }
}
