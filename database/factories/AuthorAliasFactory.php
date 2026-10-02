<?php

namespace Database\Factories;

use App\Models\AuthorAlias;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthorAlias>
 */
class AuthorAliasFactory extends Factory
{
    protected $model = AuthorAlias::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'username' => Str::slug(fake()->unique()->userName()),
            'name' => $name,
            'bio' => fake()->optional()->sentence(),
            'is_primary' => true,
        ];
    }
}
