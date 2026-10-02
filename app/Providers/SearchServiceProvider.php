<?php

namespace App\Providers;

use App\Services\Embeddings\EmbeddingService;
use App\Services\Search\SearchService;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmbeddingService::class);
        $this->app->singleton(SearchService::class);
    }

    public function boot(): void
    {
        //
    }
}
