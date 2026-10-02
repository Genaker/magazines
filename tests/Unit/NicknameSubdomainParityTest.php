<?php

namespace Tests\Unit;

use App\Models\AuthorAlias;
use App\Models\User;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\SubdomainLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NicknameSubdomainParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Features::seedDefaults();
        Features::set('author_subdomains', true);
        config(['app.url' => 'http://localhost:8888']);
        AuthorSubdomain::setBaseHost('localhost');
    }

    #[DataProvider('nicknameProvider')]
    public function test_nickname_and_subdomain_label_normalize_the_same(string $input, string $expected): void
    {
        $this->assertSame($expected, SubdomainLabel::forNickname($input));
        $this->assertSame($expected, SubdomainLabel::normalize($input));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function nicknameProvider(): array
    {
        return [
            'compact nickname' => ['CityReporter', 'cityreporter'],
            'at-style nickname without symbol' => ['cityreporter', 'cityreporter'],
            'spaced display name' => ['City Reporter', 'city-reporter'],
            'underscores become separator' => ['city_reporter', 'city-reporter'],
            'mixed case with spaces and dashes' => ['City - Reporter', 'city-reporter'],
            'duplicate separators collapse' => ['city--reporter', 'city-reporter'],
            'edge separators trimmed' => ['-city-reporter-', 'city-reporter'],
        ];
    }

    #[DataProvider('nicknameProvider')]
    public function test_subdomain_url_uses_normalized_nickname(string $input, string $expected): void
    {
        $this->assertSame(
            "http://{$expected}.localhost:8888/",
            AuthorSubdomain::buildSubdomainUrl($input),
        );
    }

    #[DataProvider('nicknameProvider')]
    public function test_subdomain_host_parses_back_to_normalized_nickname(string $input, string $expected): void
    {
        $request = Request::create("http://{$expected}.localhost:8888/", 'GET');
        $request->headers->set('HOST', "{$expected}.localhost");

        $this->assertSame($expected, AuthorSubdomain::usernameFromHost($request));
    }

    public function test_user_username_is_normalized_on_create(): void
    {
        $user = User::factory()->create(['username' => 'City Reporter']);

        $this->assertSame('city-reporter', $user->fresh()->username);
    }

    public function test_user_username_is_normalized_on_update(): void
    {
        $user = User::factory()->create(['username' => 'oldname']);

        $user->update(['username' => 'City Reporter']);

        $this->assertSame('city-reporter', $user->fresh()->username);
    }

    public function test_author_alias_username_is_normalized_on_create(): void
    {
        $user = User::factory()->create(['username' => 'aliasowner']);
        $alias = AuthorAlias::query()->create([
            'user_id' => $user->id,
            'username' => 'City Reporter',
            'name' => 'City Reporter',
        ]);

        $this->assertSame('city-reporter', $alias->fresh()->username);
    }

    public function test_route_binding_resolves_mixed_case_nickname(): void
    {
        $user = User::factory()->create(['username' => 'cityreporter']);
        $alias = $user->primaryAlias();

        $resolved = (new AuthorAlias)->resolveRouteBinding('CityReporter');

        $this->assertTrue($resolved->is($alias));
    }
}
