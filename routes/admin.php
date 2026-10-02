<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CategoryRequestController as AdminCategoryRequestController;
use App\Http\Controllers\Admin\CustomFieldController as AdminCustomFieldController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MagazineController as AdminMagazineController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\RegistrationInviteController as AdminRegistrationInviteController;
use App\Http\Controllers\Admin\SiteSettingController as AdminSiteSettingController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\TrashController as AdminTrashController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserReportController as AdminUserReportController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Auth\MagicLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('login/password', [AdminAuthenticatedSessionController::class, 'createPassword'])->name('login.password');
    Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::middleware('feature:magic_link_login')->group(function () {
        Route::post('login/magic-link', [MagicLinkController::class, 'storeAdmin'])
            ->middleware('throttle:6,1')
            ->name('login.magic.send');
        Route::get('login/magic-link/verify', [MagicLinkController::class, 'verifyAdmin'])->name('login.magic.verify');
        Route::post('login/magic-link/code', [MagicLinkController::class, 'verifyCodeAdmin'])
            ->middleware('throttle:6,1')
            ->name('login.magic.verify.code');
    });
});

Route::middleware(['auth', 'admin', 'admin.tenant_scope', 'admin.apex_url'])->group(function () {
    Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    Route::middleware('feature:user_reports')->group(function () {
        Route::get('/user-reports', [AdminUserReportController::class, 'index'])->name('user-reports.index');
        Route::post('/user-reports/{userReport}/dismiss', [AdminUserReportController::class, 'dismiss'])->name('user-reports.dismiss');
        Route::post('/user-reports/{userReport}/ban', [AdminUserReportController::class, 'ban'])->name('user-reports.ban');
    });

    Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}/edit', [AdminPostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [AdminPostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/parent-options', [AdminCategoryController::class, 'parentOptions'])->name('categories.parent-options');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{category}/merge', [AdminCategoryController::class, 'merge'])->name('categories.merge');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::middleware('feature:magazines')->group(function () {
        Route::get('/magazines/manage', [AdminMagazineController::class, 'manage'])->name('magazines.manage');
        Route::get('/magazines/{magazine:slug}/edit', [AdminMagazineController::class, 'edit'])->name('magazines.edit');
        Route::put('/magazines/{magazine:slug}', [AdminMagazineController::class, 'update'])->name('magazines.update');
        Route::delete('/magazines/{magazine:slug}', [AdminMagazineController::class, 'destroy'])->name('magazines.destroy');
        Route::get('/magazines/{magazine:slug}/posts', [AdminMagazineController::class, 'posts'])->name('magazines.posts');
        Route::get('/magazines', [AdminMagazineController::class, 'index'])->name('magazines.index');
        Route::patch('/magazines/settings', [AdminMagazineController::class, 'updateSettings'])->name('magazines.settings');
        Route::patch('/magazines/nav-order', [AdminMagazineController::class, 'reorderNav'])->name('magazines.nav.reorder');
        Route::patch('/magazines/{magazine:slug}/nav', [AdminMagazineController::class, 'updateNav'])->name('magazines.nav');
    });

    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::get('/tags/{tag}/edit', [AdminTagController::class, 'edit'])->name('tags.edit');
    Route::put('/tags/{tag}', [AdminTagController::class, 'update'])->name('tags.update');

    Route::get('/custom-fields', [AdminCustomFieldController::class, 'edit'])->name('custom-fields.edit');
    Route::put('/custom-fields', [AdminCustomFieldController::class, 'update'])->name('custom-fields.update');

    Route::middleware('feature:category_requests')->group(function () {
        Route::get('/category-requests', [AdminCategoryRequestController::class, 'index'])->name('category-requests.index');
        Route::post('/category-requests/{categoryRequest}/approve', [AdminCategoryRequestController::class, 'approve'])->name('category-requests.approve');
        Route::post('/category-requests/{categoryRequest}/reject', [AdminCategoryRequestController::class, 'reject'])->name('category-requests.reject');
    });

    Route::middleware('super_admin')->group(function () {
        Route::middleware('feature:multi_tenancy')->group(function () {
            Route::get('/tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
            Route::get('/tenants/create', [AdminTenantController::class, 'create'])->name('tenants.create');
            Route::post('/tenants', [AdminTenantController::class, 'store'])->name('tenants.store');
            Route::get('/tenants/{tenant}/edit', [AdminTenantController::class, 'edit'])->name('tenants.edit');
            Route::put('/tenants/{tenant}', [AdminTenantController::class, 'update'])->name('tenants.update');
            Route::post('/tenant-scope', [AdminTenantController::class, 'updateScope'])->name('tenant-scope.update');
        });

        Route::get('/settings', [AdminSiteSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AdminSiteSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/invites', [AdminRegistrationInviteController::class, 'store'])->name('settings.invites.store');
        Route::delete('/settings/invites/{invite}', [AdminRegistrationInviteController::class, 'destroy'])->name('settings.invites.destroy');

        Route::get('/trash', [AdminTrashController::class, 'index'])->name('trash.index');
        Route::post('/trash/users/{userId}/restore', [AdminTrashController::class, 'restoreUser'])->name('trash.users.restore');
        Route::delete('/trash/users/{userId}', [AdminTrashController::class, 'forceDeleteUser'])->name('trash.users.force-delete');
        Route::post('/trash/posts/{postId}/restore', [AdminTrashController::class, 'restorePost'])->name('trash.posts.restore');
        Route::delete('/trash/posts/{postId}', [AdminTrashController::class, 'forceDeletePost'])->name('trash.posts.force-delete');
        Route::post('/trash/magazines/{magazineId}/restore', [AdminTrashController::class, 'restoreMagazine'])->name('trash.magazines.restore');
        Route::delete('/trash/magazines/{magazineId}', [AdminTrashController::class, 'forceDeleteMagazine'])->name('trash.magazines.force-delete');

        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    });
});
