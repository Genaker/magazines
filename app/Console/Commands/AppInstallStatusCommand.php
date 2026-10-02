<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\AppInstaller;
use App\Support\DatabaseMigrator;
use App\Support\SiteBranding;
use Illuminate\Console\Command;
use Throwable;

class AppInstallStatusCommand extends Command
{
    protected $signature = 'app:install:status';

    protected $description = 'Show installation requirements, database, and site status';

    public function handle(AppInstaller $installer, DatabaseMigrator $migrator): int
    {
        $this->components->info('Requirements');

        $rows = [];
        foreach ($installer->requirements() as $key => $requirement) {
            $rows[] = [
                $key,
                $requirement['ok'] ? 'OK' : 'FAIL',
                $requirement['label'],
            ];
        }
        $this->table(['Check', 'Status', 'Detail'], $rows);

        $database = $installer->testDatabaseConnection();
        $this->newLine();
        $this->line('Database: '.($database['ok'] ? '<info>connected</info>' : '<error>'.($database['message'] ?? 'failed').'</error>'));

        if ($database['ok']) {
            $db = $installer->databaseFormDefaults();
            $this->line("  {$db['connection_label']} — {$db['host']}:{$db['port']} / {$db['database']}");
        }

        $redis = $installer->testRedisConnection();
        $cache = $installer->redisFormDefaults();
        $this->newLine();
        if ($cache['uses_redis']) {
            $this->line('Redis: '.($redis['ok'] ? '<info>connected</info>' : '<error>'.($redis['message'] ?? 'failed').'</error>'));
            if ($cache['from_env'] || filled($cache['host'])) {
                $this->line("  {$cache['host']}:{$cache['port']} (search: {$cache['search_driver']})");
            }
        } else {
            $this->line('Cache: <comment>file-based</comment> (cache '.$cache['cache_store'].', sessions '.$cache['session_driver'].', search '.$cache['search_driver'].')');
        }

        if ($database['ok']) {
            $migrations = $migrator->repositoryExists();
            $this->line('Migrations table: '.($migrations ? 'yes' : 'no'));

            try {
                $userCount = User::withoutGlobalScope('tenant')->count();
                $this->line('Users: '.$userCount);
            } catch (Throwable $e) {
                $this->line('Users: <error>cannot query — '.$e->getMessage().'</error>');
            }
        }

        $this->newLine();
        $this->line('Installed: '.($installer->isInstalled() ? '<info>yes</info> (at least one user in the database)' : '<comment>no</comment> (no users — complete /install or run db:seed)'));

        if ($installer->isInstalled()) {
            $this->line('Site name: '.SiteBranding::get('name'));
        }

        return self::SUCCESS;
    }
}
