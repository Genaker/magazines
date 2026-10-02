<?php

namespace App\Support;

use App\Models\SiteSetting;

enum HomeLayout: string
{
    case Discover = 'discover';
    case Latest = 'latest';
    case Trending = 'trending';

    /** Admin-facing label for this layout option. */
    public function label(): string
    {
        return match ($this) {
            self::Discover => 'Discover (week, trending, latest)',
            self::Latest => 'Latest only',
            self::Trending => 'Trending only',
        };
    }

    /** Human-readable description shown in admin site settings. */
    public function description(): string
    {
        return match ($this) {
            self::Discover => 'Multi-section dashboard with top stories, trending, and latest.',
            self::Latest => 'Single list of the newest published stories.',
            self::Trending => 'Top stories this week plus trending hour and day.',
        };
    }

    /** Read the active home layout from site settings. */
    public static function current(): self
    {
        $stored = SiteSetting::getValue('home_layout');

        if ($stored === null || $stored === '') {
            return self::tryFrom(config('site.home_layout', self::Discover->value)) ?? self::Discover;
        }

        return self::tryFrom($stored) ?? self::Discover;
    }

    /** Persist the selected home layout to site settings. */
    public static function set(self $layout): void
    {
        SiteSetting::setValue('home_layout', $layout->value);
    }

    /**
     * Value/label pairs for the admin home layout select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
