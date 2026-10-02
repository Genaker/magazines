<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateApiTokenCommand extends Command
{
    protected $signature = 'app:api:token
        {email : User email}
        {--name=default : Token label shown in personal_access_tokens}
        {--abilities=* : Token abilities (default: all)}';

    protected $description = 'Issue a Sanctum API token for a user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->components->error('User not found.');

            return self::FAILURE;
        }

        $abilities = $this->option('abilities');

        if ($abilities === [] || (count($abilities) === 1 && $abilities[0] === '*')) {
            $abilities = config('api.default_token_abilities', ['*']);
        }

        $token = $user->createToken($this->option('name'), $abilities);

        $this->components->success("API token created for {$user->email}.");
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->comment('Use header: Authorization: Bearer <token>');

        return self::SUCCESS;
    }
}
