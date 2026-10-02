<?php

namespace App\Console\Commands;

use App\Support\StaticAssetVersion;
use Illuminate\Console\Command;

class BumpStaticAssetVersionCommand extends Command
{
    protected $signature = 'static-version:bump {--show : Print the current version without bumping}';

    protected $description = 'Bump the static asset cache-bust version used for CSS and JS URLs';

    public function handle(): int
    {
        if ($this->option('show')) {
            $this->line(StaticAssetVersion::get());

            return self::SUCCESS;
        }

        $next = StaticAssetVersion::bump();
        $this->info("Static asset version bumped to {$next}.");

        return self::SUCCESS;
    }
}
