<?php

namespace Tests\Unit;

use App\Support\AvatarInitials;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AvatarInitialsTest extends TestCase
{
    #[DataProvider('usernameProvider')]
    public function test_from_username_uses_first_two_letters(string $username, string $expected): void
    {
        $this->assertSame($expected, AvatarInitials::fromUsername($username));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function usernameProvider(): array
    {
        return [
            'nickname' => ['demoauthor', 'DE'],
            'with at sign' => ['@cityreporter', 'CI'],
            'single character' => ['a', 'A'],
            'empty' => ['', '?'],
            'symbols only' => ['---', '?'],
        ];
    }

    public function test_background_style_is_deterministic(): void
    {
        $style = AvatarInitials::backgroundStyle('demoauthor');

        $this->assertSame($style, AvatarInitials::backgroundStyle('demoauthor'));
        $this->assertStringStartsWith('background-color: rgb(', $style);
    }
}
