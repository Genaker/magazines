<?php

namespace App\Console\Commands;

use App\Services\ScheduledPublicationService;
use Illuminate\Console\Command;

class NotifyDuePublishedPostsCommand extends Command
{
    protected $signature = 'publications:notify-due';

    protected $description = 'Notify subscribers for scheduled posts that have reached their publish time';

    public function handle(ScheduledPublicationService $scheduledPublication): int
    {
        $scheduledPublication->notifyDuePosts();

        return self::SUCCESS;
    }
}
