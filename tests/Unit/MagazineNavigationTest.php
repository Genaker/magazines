<?php

namespace Tests\Unit;

use App\Models\Magazine;
use Tests\TestCase;

class MagazineNavigationTest extends TestCase
{
    public function test_is_nav_pinned_when_timestamp_is_set(): void
    {
        $magazine = new Magazine(['nav_pinned_at' => now()]);

        $this->assertTrue($magazine->isNavPinned());
    }

    public function test_is_not_nav_pinned_when_timestamp_is_null(): void
    {
        $magazine = new Magazine(['nav_pinned_at' => null]);

        $this->assertFalse($magazine->isNavPinned());
    }
}
