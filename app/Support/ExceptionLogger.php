<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Structured error logging with optional HTTP request context. */
class ExceptionLogger
{
    /** Whether structured exception context logging is active. */
    public static function enabled(): bool
    {
        return (bool) config('logging.exception_context.enabled', true);
    }

    /**
     * Log a caught failure with exception details and optional request context.
     */
    public static function log(Throwable $exception, string $message, array $context = []): void
    {
        if (! self::enabled()) {
            return;
        }

        Log::error($message, array_merge(
            self::exceptionContext($exception),
            self::requestContext(),
            $context,
        ));
    }

    /**
     * Build request metadata for the current HTTP request or console run.
     *
     * @return array<string, mixed>
     */
    public static function requestContext(): array
    {
        if (app()->runningInConsole()) {
            return ['source' => 'console'];
        }

        if (! app()->bound('request')) {
            return ['source' => 'app'];
        }

        $request = app('request');

        if (! $request instanceof Request) {
            return ['source' => 'app'];
        }

        return [
            'source' => 'http',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
        ];
    }

    /**
     * Extract exception class, message, and source location for structured logs.
     *
     * @return array<string, mixed>
     */
    public static function exceptionContext(Throwable $exception): array
    {
        return [
            'exception' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }
}
