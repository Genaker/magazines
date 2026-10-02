<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MagazineController;
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'not_banned'])->group(function () {
    Route::post('/posts', [PostController::class, 'store'])->name('api.v1.posts.store');
    Route::post('/site-categories', [CategoryController::class, 'storeSite'])->name('api.v1.site-categories.store');

    Route::middleware('feature:magazines')->group(function () {
        Route::post('/magazines', [MagazineController::class, 'store'])->name('api.v1.magazines.store');
        Route::post('/magazines/{magazine:id}/categories', [CategoryController::class, 'storeForMagazine'])
            ->name('api.v1.magazine-categories.store');
    });
});
