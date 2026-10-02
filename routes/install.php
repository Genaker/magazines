<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::middleware('app.not_installed')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database');
    Route::post('/redis', [InstallController::class, 'storeRedis'])->name('redis');
    Route::post('/redis/skip', [InstallController::class, 'skipRedis'])->name('redis.skip');
    Route::post('/', [InstallController::class, 'store'])->name('store');
});
