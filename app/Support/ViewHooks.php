<?php

namespace App\Support;

/** Named Blade view injection points configured via config/hooks.php. */
class ViewHooks
{
    /** Register a Blade view to render at the given hook name. */
    public static function register(string $name, string $view): void
    {
        config()->push('hooks.'.$name, $view);
    }

    /**
     * Blade view names registered for a hook.
     *
     * @return list<string>
     */
    public static function views(string $name): array
    {
        return config('hooks.'.$name, []);
    }
}
