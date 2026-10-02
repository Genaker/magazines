<?php

namespace App\Contracts\Embeddings;

interface EmbeddingProvider
{
    /** Provider id (gemini, openai, none). */
    public function name(): string;

    public function isConfigured(): bool;

    /** @return list<float> */
    public function embed(string $text): array;

    public function dimensions(): int;
}
