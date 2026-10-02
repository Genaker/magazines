<?php

namespace Database\Seeders;

use App\Enums\MagazineMemberRole;
use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Services\ImageService;
use App\Support\AuthorSubdomain;
use App\Support\DemoImageGenerator;
use App\Support\Features;
use App\Support\MagazineSubdomain;
use App\Support\MediaSettings;
use App\Support\Slugger;
use App\Support\Tenancy\Tenancy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Sample tenant with domain, users, categories, and posts for local multi-tenancy demos. */
class TenantDemoSeeder extends Seeder
{
    public const TENANT_SLUG = 'tenant1';

    public const TENANT_HOST = 'tenant1.lvh.me';

    public const DEFAULT_HOST = 'lvh.me';

    public const AUTHOR_EMAIL = 'author@tenant1.test';

    public const AUTHOR_USERNAME = 'tenant1author';

    public const ADMIN_EMAIL = 'admin@tenant1.test';

    public const PASSWORD = DemoContentSeeder::DEMO_AUTHOR_PASSWORD;

    public const MAGAZINE_NAME = 'Tenant One Weekly';

    public const MAGAZINE_SLUG = 'tenant1-weekly';

    public const WELCOME_POST_TITLE = 'Welcome to Tenant 1';

    public function run(): void
    {
        Features::set('multi_tenancy', true);
        Features::set('author_subdomains', true);
        Features::set('magazine_subdomains', true);
        AuthorSubdomain::setBaseHost('lvh.me');
        AuthorSubdomain::setRedirect(true);
        MagazineSubdomain::setRedirect(true);

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => self::TENANT_SLUG],
            [
                'name' => 'Tenant 1',
                'is_default' => false,
                'is_platform' => false,
                'status' => 'active',
            ],
        );

        TenantDomain::query()->updateOrCreate(
            ['host' => self::TENANT_HOST],
            ['tenant_id' => $tenant->id, 'is_primary' => true],
        );

        TenantDomain::query()->updateOrCreate(
            ['host' => self::DEFAULT_HOST],
            ['tenant_id' => Tenant::default()->id, 'is_primary' => true],
        );

        Tenancy::flushRegisteredTenantHostsCache();

        if (Post::withoutGlobalScope('tenant')->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $generator = extension_loaded('gd') ? app(DemoImageGenerator::class) : null;
        $images = extension_loaded('gd') ? app(ImageService::class) : null;

        TenantContext::set($tenant);

        try {
            $author = $this->ensureUser(
                email: self::AUTHOR_EMAIL,
                name: 'Tenant One Author',
                username: self::AUTHOR_USERNAME,
                role: UserRole::User,
                bio: 'Stories and guides for the Tenant 1 community.',
                generator: $generator,
            );

            $this->ensureUser(
                email: self::ADMIN_EMAIL,
                name: 'Tenant One Admin',
                username: 'tenant1admin',
                role: UserRole::Admin,
                bio: 'Tenant 1 administrator.',
                generator: $generator,
            );

            $community = Category::query()->create([
                'name' => 'Community',
                'slug' => 'tenant1-community',
                'sort_order' => 1,
            ]);

            $guides = Category::query()->create([
                'name' => 'Guides',
                'slug' => 'tenant1-guides',
                'sort_order' => 2,
            ]);

            $alias = $author->primaryAlias() ?? $author->authorAliases()->firstOrFail();
            $welcomePost = null;

            foreach ($this->postDefinitions() as $index => $sample) {
                $category = ($sample['category'] ?? 'community') === 'guides' ? $guides : $community;

                $post = Post::query()->create([
                    'user_id' => $author->id,
                    'author_alias_id' => $alias->id,
                    'category_id' => $category->id,
                    'title' => $sample['title'],
                    'subtitle' => $sample['subtitle'] ?? null,
                    'slug' => Post::uniqueSlugForAlias($sample['title'], $alias->id),
                    'body' => $this->body(...$sample['paragraphs']),
                    'status' => PostStatus::Published,
                    'published_at' => now()->subDays($sample['days_ago'] ?? ($index + 1)),
                ]);

                if (! empty($sample['tags'])) {
                    $tagIds = collect($sample['tags'])->map(fn (string $name) => Tag::query()->firstOrCreate(
                        ['slug' => Slugger::unique('tenant1-'.$name, new Tag, 'slug')],
                        ['name' => ucfirst($name)],
                    )->id);
                    $post->tags()->sync($tagIds);
                }

                if ($generator && $images && ($sample['cover'] ?? true)) {
                    $processed = $images->processUpload(
                        $generator->uploadedFile($sample['title']),
                        'covers',
                        MediaSettings::PRESET_POST,
                    );
                    $post->update([
                        'cover_image' => $processed['path'],
                        'cover_variants' => $processed['variants'],
                    ]);
                }

                if ($sample['title'] === self::WELCOME_POST_TITLE) {
                    $welcomePost = $post;
                }
            }

            $magazine = Magazine::query()->create([
                'owner_id' => $author->id,
                'name' => self::MAGAZINE_NAME,
                'slug' => self::MAGAZINE_SLUG,
                'description' => 'Stories and updates from the Tenant 1 community.',
            ]);

            $magazine->members()->attach($author->id, [
                'role' => MagazineMemberRole::Owner->value,
            ]);

            $welcomePost?->update([
                'magazine_id' => $magazine->id,
                'magazine_submission_status' => MagazineSubmissionStatus::Approved,
            ]);
        } finally {
            TenantContext::reset();
        }
    }

    private function ensureUser(
        string $email,
        string $name,
        string $username,
        UserRole $role,
        string $bio,
        ?DemoImageGenerator $generator,
    ): User {
        $user = User::withoutGlobalScope('tenant')->firstOrCreate(
            ['email' => $email, 'tenant_id' => TenantContext::id()],
            [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make(self::PASSWORD),
                'role' => $role,
                'bio' => $bio,
                'email_verified_at' => now(),
            ],
        );

        if ($generator && ! $user->avatar) {
            $user->update(['avatar' => $generator->storeAvatar($name)]);
        }

        if (! $user->authorAliases()->exists()) {
            AuthorAlias::createFromUser($user, isPrimary: true);
        }

        return $user->fresh(['authorAliases']);
    }

    /** @return list<array<string, mixed>> */
    private function postDefinitions(): array
    {
        return [
            [
                'title' => self::WELCOME_POST_TITLE,
                'subtitle' => 'A separate site on its own domain',
                'paragraphs' => [
                    'Tenant 1 is an isolated community with its own authors, categories, and posts.',
                    'Visit this site at tenant1.lvh.me when running the local Docker stack.',
                ],
                'category' => 'community',
                'tags' => ['community', 'local'],
                'days_ago' => 1,
            ],
            [
                'title' => 'How Nested Author Pages Work',
                'subtitle' => 'Authors under tenant1.lvh.me',
                'paragraphs' => [
                    'When author subdomains are enabled, profiles appear at username.tenant1.lvh.me.',
                    'Each tenant keeps its own slug namespace for authors and magazines.',
                ],
                'category' => 'guides',
                'tags' => ['writing'],
                'days_ago' => 3,
            ],
            [
                'title' => 'Publishing Checklist for New Tenants',
                'paragraphs' => [
                    'Set a primary domain, invite editors, and seed a few posts before opening the site.',
                    'Platform admins manage tenants from the apex admin URL.',
                ],
                'category' => 'guides',
                'tags' => ['journalism'],
                'days_ago' => 5,
            ],
            [
                'title' => 'Neighborhood Notes from Tenant 1',
                'paragraphs' => [
                    'Markets, meetups, and small wins from readers on this tenant.',
                    'This post exists only here — it is not shared with the default tenant.',
                ],
                'category' => 'community',
                'tags' => ['local'],
                'days_ago' => 2,
            ],
        ];
    }

    private function body(string ...$paragraphs): string
    {
        return collect($paragraphs)
            ->map(fn (string $paragraph) => '<p>'.e($paragraph).'</p>')
            ->implode("\n");
    }
}
