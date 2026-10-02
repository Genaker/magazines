<?php

namespace App\Http\Controllers;

use App\Enums\MagazineSubmissionStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\AuthorAlias;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Magazine;
use App\Models\Post;
use App\Models\PostRedirect;
use App\Models\Tag;
use App\Models\User;
use App\Services\AutosaveService;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Services\MagazineNotifier;
use App\Services\PostShowService;
use App\Services\Search\SearchService;
use App\Support\ActiveAuthorAlias;
use App\Support\AuthorSubdomain;
use App\Support\CommentSettings;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use App\Support\MagazineMembership;
use App\Support\MagazinePostSubmission;
use App\Support\MagazineSubdomain;
use App\Support\Message;
use App\Support\PostUrl;
use App\Support\PostViewRecorder;
use App\Support\Seo;
use App\Support\Slugger;
use App\Support\VideoEmbed;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        private ImageService $images,
        private AutosaveService $autosave,
        private PostShowService $postShow,
        private GalleryService $galleries,
        private SearchService $search,
    ) {}

    public function mine(Request $request): View
    {
        $posts = $request->user()->posts()
            ->with(['category', 'magazine', 'authorAlias'])
            ->orderByPinThenUpdated()
            ->paginate(15);

        return view('posts.mine', ['posts' => $posts]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Post::class);

        $user = $request->user();

        return view('posts.create', array_merge($this->postFormViewData(), [
            'magazines' => $this->writableMagazines(request()),
            'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
            'activeAlias' => ActiveAuthorAlias::resolve($user),
            'selectedMagazineId' => old('magazine_id'),
            'selectedCategoryId' => old('category_id'),
        ]));
    }

    public function createGallery(Request $request): View
    {
        $this->authorize('create', Post::class);

        $user = $request->user();

        return view('posts.create-gallery', array_merge($this->postFormViewData(), [
            'magazines' => $this->writableMagazines(request()),
            'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
            'activeAlias' => ActiveAuthorAlias::resolve($user),
            'selectedMagazineId' => old('magazine_id'),
            'selectedCategoryId' => old('category_id'),
        ]));
    }

    public function createVideo(Request $request): View
    {
        $this->authorize('create', Post::class);

        $user = $request->user();

        return view('posts.create-video', array_merge($this->postFormViewData(), [
            'magazines' => $this->writableMagazines(request()),
            'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
            'activeAlias' => ActiveAuthorAlias::resolve($user),
            'selectedMagazineId' => old('magazine_id'),
            'selectedCategoryId' => old('category_id'),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('post_id')) {
            $post = Post::query()
                ->whereKey($request->integer('post_id'))
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            return $this->update($request, $post);
        }

        $this->authorize('create', Post::class);

        $data = $this->validatedPostData($request);
        $type = PostType::from($data['type'] ?? PostType::Article->value);

        $alias = $this->resolveAuthorAlias($request, $data);

        $magazineFields = $this->magazineFieldsForSave($request, $data);

        $post = DB::transaction(function () use ($request, $data, $alias, $type, $magazineFields) {
            $post = Post::query()->create([
                'user_id' => $request->user()->id,
                'author_alias_id' => $alias->id,
                'category_id' => $data['category_id'],
                'magazine_id' => $magazineFields['magazine_id'],
                'magazine_submission_status' => $magazineFields['magazine_submission_status'],
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'slug' => Post::uniqueSlugForAlias($data['title'], $alias->id),
                'type' => $type,
                'video_url' => $type === PostType::Video ? ($data['video_url'] ?? null) : null,
                'body' => $data['body'] ?? '',
                'status' => $data['status'],
                'published_at' => $this->resolvePublishedAt(
                    PostStatus::from($data['status']),
                    $request,
                    null,
                ),
                'custom_fields' => CustomFields::collect($request->input('custom_fields'), CustomFieldSchema::CONTEXT_POST),
            ]);

            if ($type === PostType::Gallery) {
                $this->syncGallery($request, $post, $data);
            }

            if ($type === PostType::Video) {
                $this->assertVideoPublishable($data['video_url'] ?? null, $data['status']);
            }

            if ($request->hasFile('cover_image')) {
                $this->autosave->applyCover($post, $request->file('cover_image'));
            }

            if ($request->hasFile('share_image')) {
                $this->autosave->applyShareImage($post, $request->file('share_image'));
            }

            $this->syncTags($post, $data['tags'] ?? '');

            if ($type === PostType::Gallery) {
                $this->assertGalleryPublishable($post, $data['status']);
            }

            return $post;
        });

        $notifyPostSubmitted = $magazineFields['magazine_submission_status'] === MagazineSubmissionStatus::Pending;

        return $this->redirectAfterSave(
            $post->fresh(['magazine', 'authorAlias']),
            $magazineFields['magazine_submission_status'],
            $notifyPostSubmitted,
        );
    }

    public function autosave(Request $request): JsonResponse
    {
        $data = $request->validate([
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'string'],
            'client_revision' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['post_id'])) {
            $post = Post::query()->findOrFail($data['post_id']);
            $this->authorize('update', $post);

            if (isset($data['client_revision']) && (int) $data['client_revision'] < $post->autosave_revision) {
                return response()->json([
                    'message' => 'A newer version exists.',
                    'revision' => $post->autosave_revision,
                    'saved_at' => $post->updated_at?->toIso8601String(),
                ], 409);
            }

            $post = $this->autosave->persistRevision($post, $request->user(), $this->autosavePayloadFromRequest($data));

            return response()->json($this->autosavePayload($post));
        }

        $this->authorize('create', Post::class);

        $title = $data['title'] ?: 'Untitled draft';
        $alias = ActiveAuthorAlias::resolve($request->user());

        $post = Post::query()->create([
            'user_id' => $request->user()->id,
            'author_alias_id' => $alias->id,
            'category_id' => $this->normalizeCategoryId($data['category_id'] ?? null),
            'title' => $title,
            'subtitle' => null,
            'slug' => Post::uniqueSlugForAlias($title, $alias->id),
            'body' => '',
            'status' => PostStatus::Draft,
        ]);

        $post = $this->autosave->persistRevision($post, $request->user(), $this->autosavePayloadFromRequest($data));

        return response()->json($this->autosavePayload($post));
    }

    public function show(AuthorAlias $alias, string $slug): View|RedirectResponse
    {
        $slug = trim($slug, '.');

        $redirect = PostRedirect::query()
            ->where('author_username', $alias->username)
            ->where('slug', $slug)
            ->with(['post.authorAlias'])
            ->first();

        if ($redirect?->post?->authorAlias && ! $redirect->post->trashed()) {
            return redirect(PostUrl::canonical($redirect->post), 301);
        }

        $post = Post::resolveForShow($alias->username, $slug);

        $this->authorize('view', $post);

        if (MagazineSubdomain::servesPost($post)) {
            return redirect(MagazineSubdomain::postUrl($post), 301);
        }

        return $this->renderShow($post);
    }

    public function showForMagazine(Magazine $magazine, string $slug): View|RedirectResponse
    {
        $post = Post::resolveForMagazineShow($magazine, $slug);

        $this->authorize('view', $post);

        return $this->renderShow($post);
    }

    private function renderShow(Post $post): View
    {
        PostViewRecorder::record(
            $post->id,
            request()->ip(),
            auth()->id(),
            request()->userAgent(),
        );

        $useDisqus = CommentSettings::useDisqus();
        $commentSort = request()->query('comments') === 'top' ? 'top' : 'new';
        $viewerData = $this->postShow->viewerData($post, auth()->user(), $commentSort, $useDisqus);

        $author = $post->authorAlias;
        $coverUrl = $this->images->url($post->cover_variants['lg'] ?? $post->cover_variants['md'] ?? $post->cover_image);

        if (! $coverUrl && $post->isGallery()) {
            $coverUrl = $post->galleryItems->first()?->url('lg');
        }

        return view('posts.show', [
            'post' => $post,
            'moreFromAuthor' => $author && ! $author->isRetired()
                ? Post::moreFromAuthor($post)
                : collect(),
            'relatedPosts' => $post->isPublished()
                ? $this->search->relatedPosts($post)
                : collect(),
            'comments' => $viewerData['comments'],
            'commentCount' => $viewerData['commentCount'],
            'commentSort' => $commentSort,
            'likedCommentIds' => $viewerData['likedCommentIds'],
            'canComment' => ! $useDisqus && (auth()->user()?->can('create', [Comment::class, $post]) ?? false),
            'liked' => $this->userLiked($post),
            'bookmarked' => $viewerData['bookmarked'],
            'readingLists' => $viewerData['readingLists'],
            'savedListIds' => $viewerData['savedListIds'],
            'seo' => Seo::forPost($post),
            'coverSrcset' => $this->images->responsiveSrcset($post->cover_variants),
            'coverUrl' => $coverUrl,
            'videoEmbedHtml' => $post->isVideo()
                ? VideoEmbed::iframeHtml($post->video_url, $post->title)
                : null,
        ]);
    }

    public function edit(Post $post): View
    {
        $this->authorize('update', $post);

        $user = request()->user();
        $post->load(['tags', 'magazine', 'authorAlias', 'galleryItems']);

        if ($post->isGallery()) {
            return view('posts.edit-gallery', array_merge($this->postFormViewData(), [
                'post' => $post,
                'magazines' => $this->writableMagazines(request()),
                'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
                'selectedMagazineId' => old('magazine_id', $post->magazine_id),
                'selectedCategoryId' => old('category_id', $post->category_id),
            ]));
        }

        if ($post->isVideo()) {
            return view('posts.edit-video', array_merge($this->postFormViewData(), [
                'post' => $post,
                'magazines' => $this->writableMagazines(request()),
                'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
                'selectedMagazineId' => old('magazine_id', $post->magazine_id),
                'selectedCategoryId' => old('category_id', $post->category_id),
                'videoEmbedHtml' => VideoEmbed::iframeHtml($post->video_url, $post->title),
            ]));
        }

        $editorState = $this->autosave->editorState($post);

        return view('posts.edit', array_merge($this->postFormViewData(), [
            'post' => $post,
            'editorState' => $editorState,
            'hasPendingAutosave' => $this->autosave->hasPendingPublishedDraft($post),
            'magazines' => $this->writableMagazines(request()),
            'coverUrl' => $this->images->url($post->cover_variants['md'] ?? $post->cover_image),
            'shareImageUrl' => $this->images->url($post->share_image_variants['md'] ?? $post->share_image),
            'aliases' => $user->authorAliases()->orderByDesc('is_primary')->orderBy('name')->get(),
            'selectedMagazineId' => old('magazine_id', $post->magazine_id),
            'selectedCategoryId' => old('category_id', $editorState['category_id'] ?? $post->category_id),
        ]));
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $data = $this->validatedPostData($request);

        $alias = $this->resolveAuthorAlias($request, $data, $post);
        $magazineFields = $this->magazineFieldsForSave($request, $data);
        $wasPending = $post->magazine_submission_status === MagazineSubmissionStatus::Pending;

        DB::transaction(function () use ($request, $post, $data, $alias, $magazineFields) {
            $newStatus = PostStatus::from($data['status']);
            $slug = $data['title'] !== $post->title
                ? Post::uniqueSlugForAlias($data['title'], $alias->id, $post->id)
                : $post->slug;

            $publishedAt = $this->resolvePublishedAt($newStatus, $request, $post->published_at);

            $post->update([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'slug' => $slug,
                'category_id' => $data['category_id'],
                'magazine_id' => $magazineFields['magazine_id'],
                'magazine_submission_status' => $magazineFields['magazine_submission_status'],
                'video_url' => $post->isVideo() ? ($data['video_url'] ?? null) : $post->video_url,
                'body' => $data['body'] ?? '',
                'status' => $newStatus,
                'published_at' => $publishedAt,
                'author_alias_id' => $alias->id,
                'custom_fields' => CustomFields::collect($request->input('custom_fields'), CustomFieldSchema::CONTEXT_POST),
            ]);

            if ($post->isGallery()) {
                $this->syncGallery($request, $post, $data);
            }

            if ($request->boolean('remove_cover')) {
                $this->images->deleteVariants($post->cover_variants);
                $post->update(['cover_image' => null, 'cover_variants' => null]);
                $this->galleries->syncCoverFromFirstItem($post->fresh());
            }

            if ($request->hasFile('cover_image')) {
                $this->autosave->applyCover($post, $request->file('cover_image'));
            }

            $this->syncShareImage($request, $post);

            $this->syncTags($post, $data['tags'] ?? '');

            if ($post->isGallery()) {
                $this->assertGalleryPublishable($post->fresh(), $data['status']);
            }

            if ($post->isVideo()) {
                $this->assertVideoPublishable($data['video_url'] ?? null, $data['status']);
            }

            if ($post->isArticle()) {
                $this->autosave->clearSnapshots($post);
            }
        });

        $notifyPostSubmitted = $magazineFields['magazine_submission_status'] === MagazineSubmissionStatus::Pending && ! $wasPending;

        return $this->redirectAfterSave(
            $post->fresh(['magazine', 'authorAlias']),
            $magazineFields['magazine_submission_status'],
            $notifyPostSubmitted,
        );
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $this->images->deleteVariants($post->cover_variants);
        $this->images->deleteVariants($post->share_image_variants);
        $this->galleries->deleteAllItems($post);
        $post->delete();

        return redirect()->route('posts.mine');
    }

    public function togglePin(Post $post): RedirectResponse
    {
        $this->authorize('pin', $post);

        $post->update([
            'pinned_at' => $post->isPinned() ? null : now(),
        ]);

        return redirect()
            ->back()
            ->with('status', $post->fresh()->isPinned() ? 'post-pinned' : 'post-unpinned');
    }

    private function validatedPostData(Request $request): array
    {
        $type = $request->input('type', PostType::Article->value);

        $rules = [
            'type' => ['nullable', 'in:article,gallery,video'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'magazine_id' => ['nullable', 'exists:magazines,id'],
            'status' => ['required', 'in:draft,published,unlisted'],
            'publish_at' => ['nullable', 'date'],
            'author_alias_id' => ['nullable', 'integer', 'exists:author_aliases,id'],
            'tags' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:'.config('media.max_upload_kb')],
            'remove_cover' => ['nullable', 'boolean'],
            'share_image' => ['nullable', 'image', 'max:'.config('media.max_upload_kb')],
            'remove_share_image' => ['nullable', 'boolean'],
            ...CustomFields::validationRules(CustomFieldSchema::CONTEXT_POST),
        ];

        if ($type === PostType::Gallery->value) {
            $rules['body'] = ['nullable', 'string'];
            $rules['gallery_images'] = ['nullable', 'array', 'max:'.config('media.max_gallery_photos')];
            $rules['gallery_images.*'] = ['image', 'max:'.config('media.max_upload_kb')];
            $rules['gallery_captions'] = ['nullable', 'array'];
            $rules['gallery_captions.*'] = ['nullable', 'string', 'max:500'];
            $rules['gallery_item_captions'] = ['nullable', 'array'];
            $rules['gallery_item_captions.*'] = ['nullable', 'string', 'max:500'];
            $rules['remove_gallery_items'] = ['nullable', 'array'];
            $rules['remove_gallery_items.*'] = ['integer'];
        } elseif ($type === PostType::Video->value) {
            $rules['body'] = ['nullable', 'string'];
            $rules['video_url'] = ['nullable', 'string', 'max:2048'];
        } else {
            $rules['body'] = ['required', 'string'];
        }

        $data = $request->validate($rules);
        $data['type'] = $type;
        $data['category_id'] = $this->normalizeCategoryId($data['category_id'] ?? null);

        if ($type === PostType::Video->value && filled($data['video_url'] ?? null) && ! VideoEmbed::isSupported($data['video_url'])) {
            throw ValidationException::withMessages([
                'video_url' => __('app.video_url_unsupported'),
            ]);
        }

        if ($data['category_id'] !== null) {
            $this->assertCategoryAllowedForPost($data['category_id'], isset($data['magazine_id']) ? (int) $data['magazine_id'] : null);
        }

        $this->assertWritableMagazine($request, isset($data['magazine_id']) ? (int) $data['magazine_id'] : null);

        return $data;
    }

    /** @return array{magazine_id: ?int, magazine_submission_status: ?MagazineSubmissionStatus} */
    private function magazineFieldsForSave(Request $request, array $data): array
    {
        $magazineId = isset($data['magazine_id']) ? (int) $data['magazine_id'] : null;

        if ($magazineId === null) {
            return [
                'magazine_id' => null,
                'magazine_submission_status' => null,
            ];
        }

        $magazine = Magazine::query()->findOrFail($magazineId);
        MagazineMembership::assertCanSubmit($request->user(), $magazine);
        $postStatus = PostStatus::from($data['status']);

        return [
            'magazine_id' => $magazineId,
            'magazine_submission_status' => MagazinePostSubmission::statusOnSave(
                $request->user(),
                $magazine,
                $postStatus,
            ),
        ];
    }

    private function redirectAfterSave(
        Post $post,
        ?MagazineSubmissionStatus $magazineStatus,
        bool $notifyPostSubmitted = false,
    ): RedirectResponse {
        if ($notifyPostSubmitted) {
            MagazineNotifier::postSubmitted($post->loadMissing(['user', 'magazine']));
        }

        $response = redirect(PostUrl::canonical($post));

        if ($magazineStatus === MagazineSubmissionStatus::Approved && $post->magazine) {
            return Message::redirectWith(
                $response,
                Message::TYPE_SUCCESS,
                __('message.post_published_in_magazine', ['magazine' => $post->magazine->name]),
            );
        }

        if ($magazineStatus === MagazineSubmissionStatus::Pending && $post->magazine) {
            return Message::redirectWith(
                $response,
                Message::TYPE_INFO,
                __('message.post_pending_magazine', ['magazine' => $post->magazine->name]),
            );
        }

        return $response;
    }

    private function assertWritableMagazine(Request $request, ?int $magazineId): void
    {
        if ($magazineId === null || ! \App\Support\Features::enabled('magazines')) {
            return;
        }

        $magazine = Magazine::query()->find($magazineId);

        if ($magazine === null) {
            throw ValidationException::withMessages([
                'magazine_id' => __('message.magazine_not_writable'),
            ]);
        }

        MagazineMembership::assertCanSubmit($request->user(), $magazine);
    }

    private function normalizeCategoryId(mixed $categoryId): ?int
    {
        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        return (int) $categoryId;
    }

    private function assertCategoryAllowedForPost(int $categoryId, ?int $magazineId): void
    {
        $category = Category::query()->find($categoryId);

        if (! $category || ! $category->isAllowedForPost($magazineId)) {
            throw ValidationException::withMessages([
                'category_id' => 'Choose a valid category for the selected magazine.',
            ]);
        }
    }

    /** @return array{siteCategories: \Illuminate\Database\Eloquent\Collection, magazineCategoryTrees: \Illuminate\Support\Collection} */
    private function postFormViewData(): array
    {
        return [
            'siteCategories' => Category::cachedForNavigation(),
            'magazineCategoryTrees' => Category::magazineCategoryTrees(),
        ];
    }

    private function assertVideoPublishable(?string $videoUrl, string $status): void
    {
        if ($status !== PostStatus::Published->value) {
            return;
        }

        if (! VideoEmbed::isSupported($videoUrl)) {
            throw ValidationException::withMessages([
                'video_url' => __('app.video_url_required'),
            ]);
        }
    }

    /** @param  array<string, mixed>  $data */
    private function syncGallery(Request $request, Post $post, array $data): void
    {
        $removeIds = collect($data['remove_gallery_items'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $this->galleries->updateCaptions($post, $data['gallery_item_captions'] ?? []);
        $this->galleries->removeItems($post, $removeIds);
        $this->galleries->addItems(
            $post,
            $request->file('gallery_images', []),
            $data['gallery_captions'] ?? [],
        );
    }

    private function assertGalleryPublishable(Post $post, string $status): void
    {
        if ($status !== PostStatus::Published->value) {
            return;
        }

        $min = config('media.min_gallery_photos');
        $count = $post->galleryItems()->count();

        if ($count < $min) {
            throw ValidationException::withMessages([
                'gallery_images' => "Add at least {$min} photos to publish a gallery ({$count} uploaded).",
            ]);
        }
    }

    private function syncShareImage(Request $request, Post $post): void
    {
        if ($request->boolean('remove_share_image')) {
            $this->images->deleteVariants($post->share_image_variants);
            $post->update(['share_image' => null, 'share_image_variants' => null]);
        }

        if ($request->hasFile('share_image')) {
            $this->autosave->applyShareImage($post, $request->file('share_image'));
        }
    }

    private function resolvePublishedAt(PostStatus $status, Request $request, ?Carbon $existing): ?Carbon
    {
        if ($status === PostStatus::Draft) {
            return null;
        }

        if ($status === PostStatus::Unlisted) {
            return $existing ?? now();
        }

        if ($request->filled('publish_at')) {
            return Carbon::parse($request->input('publish_at'));
        }

        return $existing ?? now();
    }

    private function syncTags(Post $post, string $tagsInput): void
    {
        $names = collect(explode(',', $tagsInput))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->take(config('media.max_tags_per_post', 10))
            ->values();

        $tagIds = $names->map(function (string $name) {
            return Tag::query()->firstOrCreate(
                ['slug' => Slugger::unique($name, new Tag, 'slug')],
                ['name' => $name],
            )->id;
        });

        $post->tags()->sync($tagIds);
    }

    private function userLiked(Post $post): bool
    {
        if (auth()->check()) {
            return $post->likes()->where('user_id', auth()->id())->exists();
        }

        return $post->likes()->where('ip_address', request()->ip())->exists();
    }

    /** @param  array<string, mixed>  $data */
    private function autosavePayloadFromRequest(array $data): array
    {
        return [
            'title' => $data['title'] ?? '',
            'subtitle' => $data['subtitle'] ?? null,
            'body' => $data['body'] ?? '',
            'category_id' => $data['category_id'] ?? null,
            'tags' => $data['tags'] ?? '',
        ];
    }

    private function autosavePayload(Post $post): array
    {
        $post->loadMissing('user');

        return [
            'post_id' => $post->id,
            'revision' => $post->autosave_revision,
            'saved_at' => now()->toIso8601String(),
            'share_url' => route('posts.show', [$post->authorAlias, $post->slug]),
            'edit_url' => route('posts.edit', $post),
            'cover_url' => $this->images->url($post->cover_variants['md'] ?? $post->cover_image),
        ];
    }

    private function resolveAuthorAlias(Request $request, array $data, ?Post $post = null): AuthorAlias
    {
        $user = $request->user();

        if (! empty($data['author_alias_id'])) {
            $alias = $user->authorAliases()->whereKey($data['author_alias_id'])->firstOrFail();

            return $alias;
        }

        if ($post?->author_alias_id) {
            $existing = $user->authorAliases()->whereKey($post->author_alias_id)->first();

            if ($existing) {
                return $existing;
            }
        }

        return ActiveAuthorAlias::resolve($user);
    }

    private function writableMagazines(Request $request)
    {
        if (! \App\Support\Features::enabled('magazines')) {
            return collect();
        }

        return Magazine::query()
            ->writableBy($request->user())
            ->orderBy('name')
            ->get();
    }
}
