<?php

namespace Database\Seeders;

use App\Enums\MagazineMemberRole;
use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use App\Models\User;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Support\DemoImageGenerator;
use App\Support\Features;
use App\Support\MediaSettings;
use App\Support\SiteBranding;
use App\Support\Slugger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Rich sample content — categories, posts with covers, gallery, video, magazine — for demos and tests. */
class DemoContentSeeder extends Seeder
{
    public const DEMO_AUTHOR_EMAIL = 'author@magazines.test';

    public const DEMO_AUTHOR_PASSWORD = 'password';

    public const DEMO_REPORTER_EMAIL = 'reporter@magazines.test';

    public const DEMO_TECH_EMAIL = 'tech@magazines.test';

    public function run(): void
    {
        if (Category::query()->where('slug', 'technology')->exists()) {
            return;
        }

        $images = app(ImageService::class);
        $gallery = app(GalleryService::class);
        $generator = app(DemoImageGenerator::class);
        $canGenerateImages = extension_loaded('gd');

        $author = $this->ensureAuthor(
            self::DEMO_AUTHOR_EMAIL,
            'Demo Author',
            'demoauthor',
            'Writing about technology and culture.',
            $canGenerateImages ? $generator : null,
        );
        $reporter = $this->ensureAuthor(
            self::DEMO_REPORTER_EMAIL,
            'City Reporter',
            'cityreporter',
            'Local news and community stories.',
            $canGenerateImages ? $generator : null,
        );
        $techWriter = $this->ensureAuthor(
            self::DEMO_TECH_EMAIL,
            'Tech Writer',
            'techwriter',
            'Software, AI, and the open web.',
            $canGenerateImages ? $generator : null,
        );

        $categories = $this->seedCategories();
        $this->seedTags();

        $posts = $this->postDefinitions();
        $magazinePost = null;

        foreach ($posts as $index => $sample) {
            $user = match ($sample['author'] ?? 'demo') {
                'reporter' => $reporter,
                'tech' => $techWriter,
                default => $author,
            };
            $alias = $user->primaryAlias() ?? $user->authorAliases()->firstOrFail();
            $category = $categories->firstWhere('slug', $sample['category']);
            $published = ($sample['status'] ?? PostStatus::Published) === PostStatus::Published;

            $post = Post::query()->create([
                'user_id' => $user->id,
                'author_alias_id' => $alias->id,
                'category_id' => $category->id,
                'title' => $sample['title'],
                'subtitle' => $sample['subtitle'] ?? null,
                'slug' => Post::uniqueSlugForAlias($sample['title'], $alias->id),
                'type' => $sample['type'] ?? PostType::Article,
                'video_url' => ($sample['type'] ?? PostType::Article) === PostType::Video
                    ? ($sample['video_url'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
                    : null,
                'body' => $sample['body'],
                'status' => $sample['status'] ?? PostStatus::Published,
                'published_at' => $published
                    ? now()->subDays($sample['days_ago'] ?? ($index + 1))->subHours($index * 3)
                    : null,
                'pinned_at' => ! empty($sample['pinned']) && $published ? now()->subDay() : null,
            ]);

            if (! empty($sample['tags'])) {
                $tagIds = collect($sample['tags'])->map(fn (string $name) => Tag::query()->firstOrCreate(
                    ['slug' => Slugger::unique($name, new Tag, 'slug')],
                    ['name' => ucfirst($name)],
                )->id);
                $post->tags()->sync($tagIds);
            }

            if ($canGenerateImages && ($sample['type'] ?? PostType::Article) === PostType::Gallery) {
                $captions = $sample['gallery_captions'] ?? [];
                $files = [];
                foreach ($sample['gallery_labels'] ?? ['Photo 1', 'Photo 2', 'Photo 3'] as $label) {
                    $files[] = $generator->uploadedFile($label, 1400, 933);
                }
                $gallery->addItems($post, $files, $captions);
            } elseif ($canGenerateImages && ($sample['cover'] ?? true)) {
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

            if ($published) {
                $views = $sample['views'] ?? rand(25, 120);
                PostView::query()->create([
                    'post_id' => $post->id,
                    'ip_address' => '10.0.0.'.(($post->id % 200) + 1),
                    'viewed_on' => now()->subDays(min($sample['days_ago'] ?? 1, 4))->startOfDay(),
                    'created_at' => now()->subHours(rand(1, 20)),
                ]);
                $post->update(['views_count' => $views]);
            }

            if (! empty($sample['magazine_feature'])) {
                $magazinePost = $post;
            }
        }

        $magazine = Magazine::query()->create([
            'owner_id' => $author->id,
            'name' => 'The Commons',
            'slug' => 'the-commons',
            'description' => 'A community publication for independent voices, local reporting, and open discourse.',
            'nav_pinned_at' => now(),
            'nav_sort_order' => 1,
        ]);

        if ($canGenerateImages) {
            $logo = $images->processUpload(
                $generator->uploadedFile('The Commons', 800, 800),
                'magazines',
                MediaSettings::PRESET_POST,
            );
            $magazine->update([
                'logo' => $logo['path'],
                'logo_variants' => $logo['variants'],
            ]);
        }

        $magazine->members()->attach($author->id, [
            'role' => MagazineMemberRole::Owner->value,
        ]);
        $magazine->members()->attach($reporter->id, [
            'role' => MagazineMemberRole::Editor->value,
        ]);

        $localVoices = Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Local Voices',
            'slug' => 'the-commons-local-voices',
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Community Notes',
            'slug' => 'the-commons-community-notes',
            'parent_id' => $localVoices->id,
            'sort_order' => 1,
        ]);

        Category::query()->create([
            'magazine_id' => $magazine->id,
            'name' => 'Essays',
            'slug' => 'the-commons-essays',
            'sort_order' => 2,
        ]);

        $magazinePost?->update([
            'magazine_id' => $magazine->id,
            'magazine_submission_status' => MagazineSubmissionStatus::Approved,
        ]);

        SiteBranding::seedDefaults();
        Features::seedDefaults();
    }

    private function ensureAuthor(
        string $email,
        string $name,
        string $username,
        string $bio,
        ?DemoImageGenerator $generator,
    ): User {
        $author = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make(self::DEMO_AUTHOR_PASSWORD),
                'role' => UserRole::User,
                'bio' => $bio,
                'email_verified_at' => now(),
            ],
        );

        if ($generator && ! $author->avatar) {
            $author->update(['avatar' => $generator->storeAvatar($name)]);
        }

        if (! $author->authorAliases()->exists()) {
            AuthorAlias::createFromUser($author, isPrimary: true);
        }

        return $author->fresh(['authorAliases']);
    }

    /** @return \Illuminate\Support\Collection<int, Category> */
    private function seedCategories()
    {
        $rootCategories = [
            ['name' => 'Technology', 'slug' => 'technology', 'sort_order' => 1],
            ['name' => 'Culture', 'slug' => 'culture', 'sort_order' => 2],
            ['name' => 'Science', 'slug' => 'science', 'sort_order' => 3],
            ['name' => 'Business', 'slug' => 'business', 'sort_order' => 4],
            ['name' => 'Writing', 'slug' => 'writing', 'sort_order' => 5],
        ];

        $categoryModels = collect($rootCategories)->map(
            fn (array $category) => Category::query()->create($category)
        );

        $technology = $categoryModels->firstWhere('slug', 'technology');
        $science = $categoryModels->firstWhere('slug', 'science');
        $writing = $categoryModels->firstWhere('slug', 'writing');
        $culture = $categoryModels->firstWhere('slug', 'culture');

        foreach ([
            ['parent_id' => $technology->id, 'name' => 'Web Development', 'slug' => 'web-development', 'sort_order' => 1],
            ['parent_id' => $technology->id, 'name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 2],
            ['parent_id' => $science->id, 'name' => 'Artificial Intelligence', 'slug' => 'artificial-intelligence', 'sort_order' => 1],
            ['parent_id' => $writing->id, 'name' => 'Personal Essays', 'slug' => 'personal-essays', 'sort_order' => 1],
            ['parent_id' => $culture->id, 'name' => 'Local News', 'slug' => 'local-news', 'sort_order' => 1],
        ] as $subcategory) {
            $categoryModels->push(Category::query()->create($subcategory));
        }

        return $categoryModels;
    }

    private function seedTags(): void
    {
        foreach (['laravel', 'php', 'writing', 'community', 'ai', 'journalism', 'startup', 'design', 'privacy', 'local'] as $name) {
            Tag::query()->firstOrCreate(
                ['slug' => Slugger::unique($name, new Tag, 'slug')],
                ['name' => ucfirst($name)],
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function postDefinitions(): array
    {
        return [
            [
                'title' => 'Getting Started with Laravel',
                'subtitle' => 'A quick introduction for new developers',
                'body' => $this->body(
                    'Laravel makes it easy to build modern web applications with elegant syntax and powerful tools.',
                    'In this guide we walk through routing, Eloquent, and deployment basics for a small publishing site.',
                ),
                'category' => 'web-development',
                'tags' => ['laravel', 'php'],
                'author' => 'tech',
                'pinned' => true,
                'days_ago' => 2,
                'views' => 142,
            ],
            [
                'title' => 'Why Writing in Public Matters',
                'subtitle' => 'Sharing ideas beyond the draft folder',
                'body' => $this->body(
                    'Writing online helps you clarify thinking and connect with readers around the world.',
                    'Public essays create feedback loops that private notes never will — start small, publish often.',
                ),
                'category' => 'personal-essays',
                'tags' => ['writing'],
                'magazine_feature' => true,
                'days_ago' => 4,
                'views' => 98,
            ],
            [
                'title' => 'Draft: The Future of Local AI',
                'body' => $this->body('This story is still in progress.', 'Interview notes and benchmarks will be added next week.'),
                'category' => 'artificial-intelligence',
                'status' => PostStatus::Draft,
                'author' => 'tech',
                'cover' => false,
            ],
            [
                'title' => 'Designing Community Guidelines That Work',
                'body' => $this->body(
                    'Healthy forums need clear norms, transparent moderation, and room for disagreement.',
                    'We share patterns from publications that scaled without losing trust.',
                ),
                'category' => 'culture',
                'tags' => ['community', 'journalism'],
                'days_ago' => 1,
                'views' => 76,
            ],
            [
                'title' => 'Mobile Reading Habits in 2026',
                'body' => $this->body(
                    'Readers skim headlines on phones but linger on essays with strong visuals.',
                    'Cover images and readable typography still matter more than algorithmic tricks.',
                ),
                'category' => 'mobile',
                'tags' => ['design'],
                'author' => 'tech',
                'days_ago' => 3,
                'views' => 64,
            ],
            [
                'title' => 'Town Hall: What Residents Asked For',
                'body' => $this->body(
                    'Last Thursday’s town hall packed the community center with questions about transit and parks.',
                    'Here is an annotated summary with links to public records.',
                ),
                'category' => 'local-news',
                'tags' => ['local', 'journalism'],
                'author' => 'reporter',
                'days_ago' => 5,
                'views' => 211,
            ],
            [
                'title' => 'Pricing a Newsletter Without Paywalls',
                'body' => $this->body(
                    'Small publishers experiment with tips, memberships, and sponsored editions.',
                    'We compare three models that kept editorial independence intact.',
                ),
                'category' => 'business',
                'tags' => ['startup'],
                'days_ago' => 6,
                'views' => 55,
            ],
            [
                'title' => 'Search Indexes for Independent Publishers',
                'body' => $this->body(
                    'Full-text search helps readers discover archives without proprietary platforms.',
                    'Postgres, Redis, and MariaDB each offer viable paths depending on your stack.',
                ),
                'category' => 'web-development',
                'tags' => ['laravel', 'php'],
                'author' => 'tech',
                'days_ago' => 7,
                'views' => 89,
            ],
            [
                'title' => 'Privacy Tools for Everyday Readers',
                'body' => $this->body(
                    'You do not need a security engineering degree to reduce tracking on the sites you visit.',
                    'A short checklist for browsers, RSS, and email aliases.',
                ),
                'category' => 'technology',
                'tags' => ['privacy'],
                'days_ago' => 8,
                'views' => 47,
            ],
            [
                'title' => 'Weekend Photo Walk: City Lights',
                'subtitle' => 'A gallery from the riverfront after rain',
                'type' => PostType::Gallery,
                'body' => '<p>Four scenes from Saturday night — reflections, neon, and empty tram stops.</p>',
                'category' => 'culture',
                'tags' => ['design', 'local'],
                'author' => 'reporter',
                'gallery_labels' => ['River reflections', 'Neon alley', 'Tram stop', 'Market square'],
                'gallery_captions' => ['After the rain', 'Side street', 'Last train', 'Saturday market'],
                'cover' => false,
                'days_ago' => 2,
                'views' => 133,
            ],
            [
                'title' => 'Talk Recap: Open Publishing Infrastructure',
                'subtitle' => 'Video notes from the regional media meetup',
                'type' => PostType::Video,
                'body' => $this->body(
                    'A ten-minute recap of talks about self-hosted magazines, search, and moderation workflows.',
                    'Slides and links are in the description.',
                ),
                'category' => 'technology',
                'tags' => ['journalism'],
                'author' => 'tech',
                'days_ago' => 9,
                'views' => 72,
            ],
            [
                'title' => 'Unlisted Link: Internal Style Guide',
                'body' => $this->body('Shared with contributors only — typography, tone, and headline patterns.'),
                'category' => 'writing',
                'status' => PostStatus::Unlisted,
                'cover' => false,
                'days_ago' => 0,
                'views' => 12,
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
