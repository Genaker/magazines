<?php

namespace Tests\Unit;

use App\Support\AuthorSocialLinks;
use Tests\TestCase;

class AuthorSocialLinksTest extends TestCase
{
    public function test_normalize_stores_usernames_and_urls(): void
    {
        $normalized = AuthorSocialLinks::normalize([
            'patreon' => '@demo-creator',
            'instagram' => 'https://instagram.com/myhandle',
            'mastodon' => 'mastodon.social/@me',
        ]);

        $this->assertSame('demo-creator', $normalized['patreon']);
        $this->assertSame('myhandle', $normalized['instagram']);
        $this->assertSame('https://mastodon.social/@me', $normalized['mastodon']);
    }

    public function test_for_author_builds_hrefs_for_platforms(): void
    {
        $user = new \App\Models\User([
            'social_links' => ['patreon' => 'demo-creator', 'github' => 'demo-dev'],
        ]);
        $alias = new \App\Models\AuthorAlias([
            'website' => 'https://example.com',
            'twitter_handle' => 'demoauthor',
        ]);

        $legacy = AuthorSocialLinks::legacyLinks($alias->website, $alias->twitter_handle);
        $links = AuthorSocialLinks::forAuthor($alias, $user);
        $hrefs = collect($links)->pluck('href')->all();

        $this->assertContains('https://patreon.com/demo-creator', $hrefs);
        $this->assertContains('https://github.com/demo-dev', $hrefs);
        $this->assertSame('https://example.com', $legacy[0]['href']);
    }
}
