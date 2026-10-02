<?php

namespace App\Providers;

use App\Auth\TenantAwareUserProvider;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\AdminHeader;
use App\Support\AdminPath;
use App\Support\AppInstaller;
use App\Support\Features;
use App\Support\SubdomainSession;
use App\Support\StaticAssetVersion;
use App\Support\Theme;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use App\Observers\CategoryObserver;
use App\Observers\PostObserver;
use App\Observers\TagObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::provider('tenant_aware_eloquent', function ($app, array $config) {
            return new TenantAwareUserProvider($app['hash'], $config['model']);
        });

        app(AppInstaller::class)->configureRuntimeForInstallPhase();

        if (app(AppInstaller::class)->isInstalled()) {
            SubdomainSession::configure();
            AdminPath::syncSessionConfig();
            config([
                'filesystems.disks.public.url' => rtrim((string) config('app.url'), '/').'/storage',
            ]);
        }

        // Docker Compose env must win over mounted host .env
        if ($host = getenv('MAIL_HOST')) {
            config([
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => getenv('MAIL_PORT') ?: config('mail.mailers.smtp.port'),
                'mail.mailpit_web_url' => getenv('MAILPIT_WEB_URL') ?: config('mail.mailpit_web_url'),
                'mail.from.address' => getenv('MAIL_FROM_ADDRESS') ?: config('mail.from.address'),
                'mail.from.name' => getenv('MAIL_FROM_NAME') ?: config('mail.from.name'),
            ]);
        }

        Post::observe(PostObserver::class);
        Category::observe(CategoryObserver::class);
        Tag::observe(TagObserver::class);
        User::observe(UserObserver::class);

        Gate::policy(\App\Models\AuthorAlias::class, \App\Policies\AuthorAliasPolicy::class);
        Gate::policy(\App\Models\Post::class, \App\Policies\PostPolicy::class);
        Gate::policy(\App\Models\Comment::class, \App\Policies\CommentPolicy::class);
        Gate::policy(\App\Models\Magazine::class, \App\Policies\MagazinePolicy::class);
        Gate::policy(\App\Models\ReadingList::class, \App\Policies\ReadingListPolicy::class);

        Blade::if('feature', fn (string $feature): bool => Features::enabled($feature));

        Theme::register();

        Vite::createAssetPathsUsing(
            fn (string $path, $secure = null) => StaticAssetVersion::append(asset($path, $secure)),
        );

        View::composer('layouts.navigation', function ($view): void {
            $user = auth()->user();

            $view->with(
                'unreadNotificationsCount',
                $user ? $user->unreadNotifications()->count() : 0,
            );
        });

        View::composer('layouts.admin', function ($view): void {
            $view->with('adminHeader', AdminHeader::data());
        });
    }
}
