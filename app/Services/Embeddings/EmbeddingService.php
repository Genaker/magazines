<?php

namespace App\Services\Embeddings;

use App\Contracts\Embeddings\EmbeddingProvider;
use App\Support\SearchTextPreprocessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Resolves embedding provider and caches query vectors. */
class EmbeddingService
{
    public function __construct(
        private GeminiEmbeddingProvider $gemini,
        private OpenAiEmbeddingProvider $openai,
        private NullEmbeddingProvider $null,
        private SearchTextPreprocessor $preprocessor,
    ) {}

    public function provider(): EmbeddingProvider
    {
        return match ((string) config('search.embeddings.provider', 'none')) {
            'gemini' => $this->gemini->isConfigured() ? $this->gemini : $this->null,
            'openai' => $this->openai->isConfigured() ? $this->openai : $this->null,
            default => $this->null,
        };
    }

    public function semanticEnabled(): bool
    {
        if (! $this->provider()->isConfigured()) {
            return false;
        }

        $enabled = config('search.semantic.enabled');

        if ($enabled === null) {
            return true;
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return list<float> */
    public function embedForIndex(string $text): array
    {
        if (! $this->semanticEnabled()) {
            return [];
        }

        return $this->embed($text);
    }

    /** @return list<float> */
    public function embedQuery(string $query): array
    {
        if (! $this->semanticEnabled()) {
            return [];
        }

        $normalized = $this->preprocessor->forEmbedding($query);
        $ttl = (int) config('search.embeddings.query_cache_ttl', 900);

        if ($normalized === '') {
            return [];
        }

        return Cache::remember(
            'search:query-embed:'.sha1($normalized),
            $ttl,
            fn () => $this->embed($normalized),
        );
    }

    /** @return list<float> */
    private function embed(string $text): array
    {
        if ($text === '') {
            return [];
        }

        try {
            return $this->provider()->embed($text);
        } catch (\Throwable $exception) {
            Log::warning('Embedding request failed', [
                'provider' => $this->provider()->name(),
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
