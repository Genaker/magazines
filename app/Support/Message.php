<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ViewErrorBag;

/** User-facing banner messages (success, error, warning, info) stored in session. */
final class Message
{
    public const TYPE_SUCCESS = 'success';

    public const TYPE_ERROR = 'error';

    public const TYPE_WARNING = 'warning';

    public const TYPE_INFO = 'info';

    public const SESSION_KEY = 'messages';

    /** @return array{type: string, message: string} */
    public static function item(string $type, string $message): array
    {
        return ['type' => $type, 'message' => $message];
    }

    public static function push(string $type, string $message): void
    {
        $messages = session(self::SESSION_KEY, []);
        $messages[] = self::item($type, $message);
        session()->flash(self::SESSION_KEY, $messages);
    }

    public static function success(string $message): void
    {
        self::push(self::TYPE_SUCCESS, $message);
    }

    public static function error(string $message): void
    {
        self::push(self::TYPE_ERROR, $message);
    }

    public static function warning(string $message): void
    {
        self::push(self::TYPE_WARNING, $message);
    }

    public static function info(string $message): void
    {
        self::push(self::TYPE_INFO, $message);
    }

    public static function redirectWith(RedirectResponse $response, string $type, string $message): RedirectResponse
    {
        $messages = session(self::SESSION_KEY, []);
        $messages[] = self::item($type, $message);

        return $response->with(self::SESSION_KEY, $messages);
    }

    /** Map legacy session("status") keys to typed messages. */
    public static function fromStatus(string $status): ?array
    {
        $key = 'message.status.'.$status;
        $text = __($key);

        if ($text === $key) {
            return null;
        }

        $type = match (true) {
            str_contains($status, 'reject') || str_contains($status, 'invalid') || str_contains($status, 'error') => self::TYPE_ERROR,
            str_contains($status, 'pending') || str_contains($status, 'already') => self::TYPE_WARNING,
            str_contains($status, 'approved') || str_contains($status, 'requested') || str_contains($status, 'updated')
                || str_contains($status, 'created') || str_contains($status, 'sent') || str_contains($status, 'read')
                || str_contains($status, 'invited') || str_contains($status, 'pinned') => self::TYPE_SUCCESS,
            default => self::TYPE_INFO,
        };

        return self::item($type, $text);
    }

    /** @return list<array{type: string, message: string}> */
    public static function collect(?ViewErrorBag $errors = null): array
    {
        $messages = session(self::SESSION_KEY, []);
        if (! is_array($messages)) {
            $messages = [];
        }

        if (($status = session('status')) !== null && $status !== '') {
            $mapped = self::fromStatus((string) $status);
            if ($mapped !== null) {
                $messages[] = $mapped;
            }
        }

        if ($errors !== null) {
            self::appendValidationErrors($messages, $errors);
        }

        return $messages;
    }

    /** @param  list<array{type: string, message: string}>  $messages */
    private static function appendValidationErrors(array &$messages, ViewErrorBag $errors): void
    {
        foreach ($errors->getMessages() as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = self::item(self::TYPE_ERROR, (string) $error);
            }
        }
    }
}
