<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\WebManifestController;
use App\Http\Controllers\UserReportController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\AuthorAliasController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\AuthorStatsController;
use App\Http\Controllers\AuthorSubscriptionController;
use App\Http\Controllers\ReadingListController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentLikeController;
use App\Http\Controllers\UserBlockController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryRequestController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\MagazineController;
use App\Http\Controllers\MagazineJoinRequestController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SyndicationController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/site.webmanifest', WebManifestController::class)->name('manifest');
Route::get('/feed.rss', [SyndicationController::class, 'rss'])->name('syndication.rss');
Route::get('/feed.atom', [SyndicationController::class, 'atom'])->name('syndication.atom');
Route::get('/sitemap.xml', [SyndicationController::class, 'sitemap'])->name('syndication.sitemap');
Route::get('/robots.txt', [SyndicationController::class, 'robots'])->name('syndication.robots');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
Route::get('/subscriptions/unsubscribe/{subscriber}/{author}', [AuthorSubscriptionController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('users.subscribe.unsubscribe');

Route::get('/', [FeedController::class, 'home'])->name('home');
Route::get('/dashboard', fn () => redirect()->route('home'))->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/discover', [FeedController::class, 'discover'])->name('discover');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/authors', [AuthorController::class, 'index'])->name('authors.index');
Route::middleware('feature:magazines')->group(function () {
    Route::get('/magazines', [MagazineController::class, 'index'])->name('magazines.index');
    Route::get('/magazine/{magazine:slug}', [MagazineController::class, 'show'])->name('magazines.show');
    Route::get('/magazine/{magazine:slug}/join-request', [MagazineJoinRequestController::class, 'create'])->name('magazines.join-requests.create');
});

Route::middleware(['auth', 'verified', 'feature:category_requests'])->group(function () {
    Route::get('/category/request', [CategoryRequestController::class, 'create'])->name('category-requests.create');
    Route::post('/category/request', [CategoryRequestController::class, 'store'])->name('category-requests.store');
});

Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/category/{slug}/feed.rss', [SyndicationController::class, 'categoryRss'])->name('categories.rss');
Route::get('/tag/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/tags/suggest', [TagController::class, 'suggest'])->name('tags.suggest');
Route::get('/@{alias:username}/avatar', [AvatarController::class, 'author'])->name('authors.avatar');
Route::get('/@{alias:username}', [AuthorController::class, 'show'])->name('authors.show');
Route::get('/@{alias:username}/{slug}', [PostController::class, 'show'])->name('posts.show');

Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->name('posts.like');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/avatar', [AvatarController::class, 'profile'])->name('profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/aliases', [AuthorAliasController::class, 'index'])->name('profile.aliases');
    Route::post('/profile/aliases', [AuthorAliasController::class, 'store'])->name('profile.aliases.store');
    Route::post('/profile/aliases/{alias}/switch', [AuthorAliasController::class, 'switch'])->name('profile.aliases.switch');
    Route::delete('/profile/aliases/{alias}', [AuthorAliasController::class, 'destroy'])->name('profile.aliases.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/me/posts', [PostController::class, 'mine'])->name('posts.mine');
    Route::get('/me/stats', [AuthorStatsController::class, 'index'])->name('stats.index');
    Route::post('/write/autosave', [PostController::class, 'autosave'])->name('posts.autosave');
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('media.upload');
    Route::get('/write', [PostController::class, 'create'])->name('posts.create');
    Route::get('/write/gallery', [PostController::class, 'createGallery'])->name('posts.create.gallery');
    Route::get('/write/video', [PostController::class, 'createVideo'])->name('posts.create.video');
    Route::post('/write', [PostController::class, 'store'])->name('posts.store');
    Route::get('/write/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/write/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/write/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::post('/write/{post}/pin', [PostController::class, 'togglePin'])->name('posts.pin');

    Route::middleware('feature:reading_lists')->group(function () {
        Route::post('/posts/{post}/bookmark', [ReadingListController::class, 'toggleDefault'])->name('posts.bookmark');
        Route::get('/me/bookmarks', fn () => redirect()->route('reading-lists.index'))->name('bookmarks.index');
        Route::get('/me/lists', [ReadingListController::class, 'index'])->name('reading-lists.index');
        Route::post('/me/lists', [ReadingListController::class, 'store'])->name('reading-lists.store');
        Route::get('/me/lists/{readingList}', [ReadingListController::class, 'show'])->name('reading-lists.show');
        Route::patch('/me/lists/{readingList}', [ReadingListController::class, 'update'])->name('reading-lists.update');
        Route::delete('/me/lists/{readingList}', [ReadingListController::class, 'destroy'])->name('reading-lists.destroy');
        Route::post('/me/lists/{readingList}/posts/{post}', [ReadingListController::class, 'togglePost'])->name('reading-lists.posts.toggle');
    });

    Route::get('/me/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/me/notifications/read', [NotificationController::class, 'markAllRead'])->name('notifications.read');
    Route::post('/me/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read-one');

    Route::post('/users/{user}/follow', [FollowController::class, 'toggleUser'])->name('users.follow');
    Route::post('/users/{user}/subscribe', [AuthorSubscriptionController::class, 'toggle'])->name('users.subscribe');
    Route::post('/categories/{category:id}/follow', [FollowController::class, 'toggleCategory'])->name('categories.follow');
    Route::post('/tags/{tag}/follow', [FollowController::class, 'toggleTag'])->name('tags.follow');
    Route::middleware('feature:magazines')->group(function () {
        Route::post('/magazines/{magazine:id}/follow', [FollowController::class, 'toggleMagazine'])->name('magazines.follow');
    });

    Route::middleware('feature:category_requests')->group(function () {
        Route::get('/me/category-requests', [CategoryRequestController::class, 'mine'])->name('category-requests.mine');
    });

    Route::prefix('me/moderation')->name('moderation.')->group(function () {
        Route::get('/', [ModerationController::class, 'index'])->name('index');
        Route::get('/categories/{category:slug}', [ModerationController::class, 'category'])->name('categories.show');
        Route::get('/tags/{tag:slug}', [ModerationController::class, 'tag'])->name('tags.show');
        Route::post('/posts/{post}/hide-feed', [ModerationController::class, 'hideFromFeed'])->name('posts.hide-feed');
        Route::post('/posts/{post}/show-feed', [ModerationController::class, 'showInFeed'])->name('posts.show-feed');
        Route::put('/posts/{post}/category', [ModerationController::class, 'updateCategory'])->name('posts.category');
        Route::put('/posts/{post}/tags', [ModerationController::class, 'updateTags'])->name('posts.tags');
    });

    Route::middleware('feature:user_reports')->group(function () {
        Route::post('/users/{user}/report', [UserReportController::class, 'store'])->name('users.report');
    });
    Route::post('/users/{user}/block', [UserBlockController::class, 'toggle'])->name('users.block');
    Route::delete('/profile/blocked-users/{user}', [UserBlockController::class, 'destroy'])->name('profile.blocked-users.destroy');

    Route::middleware('feature:comments')->group(function () {
        Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('posts.comments.store');
        Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
        Route::post('/comments/{comment}/like', [CommentLikeController::class, 'toggle'])->name('comments.like');
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });

    Route::middleware('feature:magazines')->group(function () {
        Route::get('/me/magazines', [MagazineController::class, 'mine'])->name('magazines.mine');
        Route::get('/magazines/create', [MagazineController::class, 'create'])->name('magazines.create');
        Route::post('/magazines', [MagazineController::class, 'store'])->name('magazines.store');
        Route::get('/magazine/{magazine:slug}/submissions', [MagazineController::class, 'submissions'])->name('magazines.submissions');
        Route::patch('/magazine/{magazine:slug}/submission-settings', [MagazineController::class, 'updateSubmissionSettings'])->name('magazines.submission-settings.update');
        Route::post('/magazine/{magazine:slug}/submissions/{post}/approve', [MagazineController::class, 'approveSubmission'])->name('magazines.submissions.approve');
        Route::post('/magazine/{magazine:slug}/submissions/{post}/reject', [MagazineController::class, 'rejectSubmission'])->name('magazines.submissions.reject');
        Route::post('/magazine/{magazine:slug}/members', [MagazineController::class, 'inviteMember'])->name('magazines.members.invite');
        Route::post('/magazine/{magazine:slug}/join-request', [MagazineJoinRequestController::class, 'store'])->name('magazines.join-requests.store');
        Route::post('/magazine/{magazine:slug}/join-requests/{joinRequest}/approve', [MagazineJoinRequestController::class, 'approve'])->name('magazines.join-requests.approve');
        Route::post('/magazine/{magazine:slug}/join-requests/{joinRequest}/reject', [MagazineJoinRequestController::class, 'reject'])->name('magazines.join-requests.reject');
        Route::post('/magazine/{magazine:slug}/posts/{post}/submit', [MagazineController::class, 'submitPost'])->name('magazines.posts.submit');
    });
});

require __DIR__.'/auth.php';
