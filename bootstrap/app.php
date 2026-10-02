<?php

use App\Console\Scheduling\ScheduleRegistrar;
use App\Support\AdminPath;
use App\Support\ExceptionLogger;
use App\Support\MessageExceptionHandler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/install.php'));

            AdminPath::syncSessionConfig();

            Route::middleware('web')
                ->prefix(AdminPath::prefix())
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): string {
            if (AdminPath::appliesTo($request)) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'not_banned' => \App\Http\Middleware\EnsureUserIsNotBanned::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
            'registration.available' => \App\Http\Middleware\EnsureRegistrationAvailable::class,
            'app.installed' => \App\Http\Middleware\EnsureAppIsInstalled::class,
            'app.not_installed' => \App\Http\Middleware\EnsureAppNotInstalled::class,
            'admin.tenant_scope' => \App\Http\Middleware\ApplyAdminTenantScope::class,
            'admin.apex_url' => \App\Http\Middleware\ForceAdminApexUrl::class,
        ]);

        $middleware->prepend(\App\Http\Middleware\ServeMagazineSubdomain::class);
        $middleware->prepend(\App\Http\Middleware\ServeAuthorSubdomain::class);
        $middleware->prepend(\App\Http\Middleware\RedirectLegacyLocalhostSubdomain::class);
        $middleware->prepend(\App\Http\Middleware\IdentifyTenant::class);
        $middleware->prepend(\App\Http\Middleware\ConfigureAdminSession::class);
        $middleware->prepend(\App\Http\Middleware\ConfigureSubdomainSession::class);

        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureSessionMatchesTenant::class,
            \App\Http\Middleware\EnsureUserIsNotBanned::class,
            \App\Http\Middleware\EnsureAppIsInstalled::class,
            \App\Http\Middleware\RedirectToAuthorSubdomain::class,
            \App\Http\Middleware\RedirectToMagazineSubdomain::class,
        ]);

        $middleware->appendToGroup('api', [
            \App\Http\Middleware\EnsureAppIsInstalled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        if (ExceptionLogger::enabled()) {
            $exceptions->context(fn () => ExceptionLogger::requestContext());
        }

        Event::listen(JobFailed::class, function (JobFailed $event): void {
            ExceptionLogger::log(
                $event->exception,
                'Queue job failed',
                [
                    'job' => $event->job->resolveName(),
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName,
                ],
            );
        });

        $exceptions->renderable(function (\Throwable $e, Request $request) {
            return MessageExceptionHandler::renderResponse($e, $request);
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        app(ScheduleRegistrar::class)->register($schedule);
    })
    ->create();
