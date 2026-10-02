<?php

namespace App\Services\Embeddings;

use App\Contracts\Embeddings\EmbeddingProvider;

/** No-op provider when semantic search is disabled. */
class NullEmbeddingProvider implements EmbeddingProvider
{
    public function name(): string
    {
        return 'none';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function embed(string $text): array
    {
        return [];
    }

    public function dimensions(): int
    {
        return (int) config('search.embeddings.dimensions', 768);
    }
}
