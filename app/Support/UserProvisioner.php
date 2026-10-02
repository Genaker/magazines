<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\User;
use App\Support\SubdomainLabel;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Create verified users with a primary author alias (CLI, installer, seeds). */
class UserProvisioner
{
    /** @param  array{name: string, username: string, email: string, password: string, bio?: string|null}  $data */
    public function create(
        array $data,
        UserRole $role,
        bool $verified = true,
    ): User {
        $data['username'] = SubdomainLabel::forNickname($data['username']);

        if (User::query()->where('email', $data['email'])->orWhere('username', $data['username'])->exists()) {
            throw new RuntimeException('A user with that email or username already exists.');
        }

        $password = $data['password'];

        if (! str_starts_with($password, '$2y$') && ! str_starts_with($password, '$2a$')) {
            $password = Hash::make($password);
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $password,
            'role' => $role,
            'bio' => $data['bio'] ?? null,
        ]);

        if ($verified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        AuthorAlias::createFromUser($user, isPrimary: true);

        return $user;
    }
}
