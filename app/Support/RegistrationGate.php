<?php

namespace App\Support;

use App\Models\RegistrationInvite;

/** Determines whether and how new users can register based on feature toggles. */
class RegistrationGate
{
    /** Whether open self-service registration is enabled. */
    public static function allowsOpenRegistration(): bool
    {
        return Features::enabled('registration');
    }

    /** Whether invite-code registration is enabled. */
    public static function allowsInviteRegistration(): bool
    {
        return Features::enabled('registration_invites');
    }

    /** Whether register routes should be registered at all. */
    public static function registerRoutesEnabled(): bool
    {
        return static::allowsOpenRegistration() || static::allowsInviteRegistration();
    }

    /** Invite code is mandatory when open registration is off but invites are on. */
    public static function requiresInviteCode(): bool
    {
        return ! static::allowsOpenRegistration() && static::allowsInviteRegistration();
    }

    /** Find a usable invite by code, or null when the code is missing or invalid. */
    public static function findValidInvite(?string $code): ?RegistrationInvite
    {
        if ($code === null || $code === '') {
            return null;
        }

        $invite = RegistrationInvite::query()
            ->where('code', strtoupper(trim($code))) // codes stored uppercase
            ->first();

        return $invite?->isValid() ? $invite : null;
    }
}
