<?php

namespace Tests\Unit;

use App\Support\SubdomainLabel;
use Tests\TestCase;

class SubdomainLabelTest extends TestCase
{
    public function test_replaces_spaces_with_hyphens(): void
    {
        $this->assertSame('the-commons', SubdomainLabel::normalize('The Commons'));
    }

    public function test_collapses_duplicate_hyphens_from_spaces_and_dashes(): void
    {
        $this->assertSame('foo-bar', SubdomainLabel::normalize('foo - bar'));
        $this->assertSame('foo-bar', SubdomainLabel::normalize('foo--bar'));
        $this->assertSame('foo-bar', SubdomainLabel::normalize('foo  -  bar'));
    }

    public function test_trims_separator_from_edges(): void
    {
        $this->assertSame('the-commons', SubdomainLabel::normalize('- The Commons -'));
    }

    public function test_lowercases_label(): void
    {
        $this->assertSame('demoauthor', SubdomainLabel::normalize('DemoAuthor'));
    }

    public function test_for_nickname_uses_same_rules_as_subdomain(): void
    {
        $this->assertSame('city-reporter', SubdomainLabel::forNickname('City Reporter'));
        $this->assertSame('cityreporter', SubdomainLabel::forNickname('CityReporter'));
        $this->assertSame(
            SubdomainLabel::normalize('City Reporter'),
            SubdomainLabel::forNickname('City Reporter'),
        );
    }

    public function test_strips_leading_at_sign_from_username_input(): void
    {
        $this->assertSame('egorshitikov', SubdomainLabel::forNickname('@egorshitikov'));
        $this->assertSame('user', SubdomainLabel::forNickname(' @user '));
        $this->assertSame('user', SubdomainLabel::forNickname('@@user'));
    }
}
