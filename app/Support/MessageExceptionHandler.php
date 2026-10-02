<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Turn caught business-rule HTTP exceptions into banner messages; real bugs use Laravel's error page. */
final class MessageExceptionHandler
{
    /** Only intentional 422 responses with a user message (abort_if, abort_unless, etc.). */
    private const LOGICAL_STATUS = 422;

    public static function renderResponse(\Throwable $exception, Request $request): ?RedirectResponse
    {
        if (! $exception instanceof HttpExceptionInterface) {
            return null;
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        if ($exception->getStatusCode() !== self::LOGICAL_STATUS) {
            return null;
        }

        if ($request->isMethodSafe()) {
            return null;
        }

        $text = trim((string) $exception->getMessage());

        if ($text === '') {
            return null;
        }

        $redirect = redirect()->back();

        if ($request->hasSession()) {
            $redirect = $redirect->withInput($request->except('password', 'password_confirmation', '_token'));
        }

        return Message::redirectWith($redirect, Message::TYPE_ERROR, $text);
    }
}
