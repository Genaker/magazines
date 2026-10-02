<?php

namespace App\Console\Commands;

use App\Models\SiteSetting;
use App\Support\AdminPath;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\HomeLayout;
use App\Support\MagazineSubdomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class SiteSettingCommand extends Command
{
    protected $signature = 'site:setting
        {action : list, get, set, or unset}
        {key? : Feature name, site setting key, or feature_* key}
        {value? : Value for set (booleans: on/off, true/false, 1/0, yes/no)}';

    protected $description = 'View or change site settings and feature flags from the CLI';

    public function handle(): int
    {
        if (! Schema::hasTable('site_settings')) {
            $this->components->error('site_settings table missing. Run migrations first.');

            return self::FAILURE;
        }

        Features::seedDefaults();

        return match ($this->argument('action')) {
            'list' => $this->listSettings(),
            'get' => $this->getSetting(),
            'set' => $this->setSetting(),
            'unset' => $this->unsetSetting(),
            default => $this->invalidAction(),
        };
    }

    private function listSettings(): int
    {
        $this->line('Features:');

        foreach (Features::definitions() as $feature => $definition) {
            $enabled = Features::enabled($feature) ? 'on' : 'off';
            $default = ($definition['default'] ?? true) ? 'on' : 'off';
            $label = $definition['label'] ?? $feature;

            $this->line("  {$feature}: {$enabled} (default: {$default}) — {$label}");
        }

        $this->newLine();
        $this->line('Site settings:');

        $rows = SiteSetting::query()->orderBy('key')->get(['key', 'value']);

        if ($rows->isEmpty()) {
            $this->line('  (none stored)');

            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            $value = $row->value === null || $row->value === '' ? '(empty)' : $row->value;
            $this->line("  {$row->key}: {$value}");
        }

        return self::SUCCESS;
    }

    private function getSetting(): int
    {
        try {
            $key = $this->requireKey();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($feature = $this->resolveFeatureKey($key)) {
            $enabled = Features::enabled($feature) ? 'on' : 'off';
            $this->line("{$feature}: {$enabled}");

            return self::SUCCESS;
        }

        $storageKey = $this->storageKey($key);

        if ($key === 'admin_path') {
            $this->line('admin_path: '.AdminPath::prefix());

            return self::SUCCESS;
        }

        $value = SiteSetting::getValue($storageKey);

        if ($value === null) {
            $this->components->warn("{$storageKey}: (not set)");
        } else {
            $this->line("{$storageKey}: {$value}");
        }

        return self::SUCCESS;
    }

    private function setSetting(): int
    {
        try {
            $key = $this->requireKey();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $value = $this->argument('value');

        if ($value === null) {
            $this->components->error('Provide a value for set, e.g. site:setting set author_subdomains on');

            return self::FAILURE;
        }

        try {
            if ($feature = $this->resolveFeatureKey($key)) {
                Features::set($feature, $this->parseBoolean($value));
                $state = Features::enabled($feature) ? 'on' : 'off';
                $this->components->info("Set feature {$feature} to {$state}.");

                return self::SUCCESS;
            }

            $this->applyStructuredSetting($key, $value);

            $storageKey = $this->storageKey($key);
            $stored = SiteSetting::getValue($storageKey);
            $display = $stored === null || $stored === '' ? '(empty)' : $stored;
            $this->components->info("Set {$storageKey} to {$display}.");
        } catch (InvalidArgumentException|RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function unsetSetting(): int
    {
        try {
            $key = $this->requireKey();
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($feature = $this->resolveFeatureKey($key)) {
            SiteSetting::forgetValue('feature_'.$feature);
            $default = (Features::definitions()[$feature]['default'] ?? true) ? 'on' : 'off';
            $this->components->info("Removed feature override for {$feature}; using default ({$default}).");

            return self::SUCCESS;
        }

        if ($key === 'author_subdomain_base_host') {
            AuthorSubdomain::setBaseHost(null);
            $this->components->info('Cleared author_subdomain_base_host; using APP_URL host.');

            return self::SUCCESS;
        }

        if ($key === 'admin_path') {
            AdminPath::set(null);
            $this->components->info('Cleared admin_path; using ADMIN_PATH env or default (admin).');

            return self::SUCCESS;
        }

        SiteSetting::forgetValue($this->storageKey($key));
        $this->components->info('Removed '.$this->storageKey($key).'.');

        return self::SUCCESS;
    }

    private function applyStructuredSetting(string $key, string $value): void
    {
        match ($key) {
            'author_subdomain_base_host' => AuthorSubdomain::setBaseHost($value === '' ? null : $value),
            'author_subdomain_redirect' => AuthorSubdomain::setRedirect($this->parseBoolean($value)),
            'magazine_subdomain_redirect' => MagazineSubdomain::setRedirect($this->parseBoolean($value)),
            'subdomain_label_separator' => SiteSetting::setValue('subdomain_label_separator', $value === '' ? null : $value),
            'home_layout' => HomeLayout::set(
                HomeLayout::tryFrom($value)
                    ?? throw new InvalidArgumentException('home_layout must be discover, latest, or trending.'),
            ),
            'admin_path' => AdminPath::set($value === '' ? null : AdminPath::normalize($value)),
            default => SiteSetting::setValue($this->storageKey($key), $value === '' ? null : $value),
        };
    }

    private function requireKey(): string
    {
        $key = trim((string) $this->argument('key'));

        if ($key === '') {
            throw new RuntimeException('Provide a key, e.g. site:setting get author_subdomains');
        }

        return $key;
    }

    private function storageKey(string $key): string
    {
        if (str_starts_with($key, 'feature_')) {
            return $key;
        }

        return $key;
    }

    private function resolveFeatureKey(string $key): ?string
    {
        $definitions = Features::definitions();

        if (array_key_exists($key, $definitions)) {
            return $key;
        }

        if (str_starts_with($key, 'feature_')) {
            $feature = substr($key, strlen('feature_'));

            return array_key_exists($feature, $definitions) ? $feature : null;
        }

        return null;
    }

    private function parseBoolean(string $value): bool
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw new InvalidArgumentException("Invalid boolean value [{$value}]. Use on/off, true/false, 1/0, or yes/no."),
        };
    }

    private function invalidAction(): int
    {
        $this->components->error('Action must be list, get, set, or unset.');

        return self::FAILURE;
    }
}
