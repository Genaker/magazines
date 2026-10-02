<?php

namespace App\Services\Embeddings;

use App\Contracts\Embeddings\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Google Gemini embedding API. */
class GeminiEmbeddingProvider implements EmbeddingProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        return filled(config('search.embeddings.gemini.api_key'));
    }

    public function dimensions(): int
    {
        return (int) config('search.embeddings.dimensions', 768);
    }

    public function embed(string $text): array
    {
        $apiKey = (string) config('search.embeddings.gemini.api_key');
        $model = (string) config('search.embeddings.gemini.model', 'text-embedding-004');
        $baseUrl = rtrim((string) config('search.embeddings.gemini.base_url'), '/');

        $response = Http::timeout(30)
            ->acceptJson()
            ->post("{$baseUrl}/models/{$model}:embedContent?key={$apiKey}", [
                'content' => [
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini embedding request failed: '.$response->body());
        }

        $values = $response->json('embedding.values');

        if (! is_array($values) || $values === []) {
            throw new RuntimeException('Gemini embedding response missing values.');
        }

        return array_map('floatval', $values);
    }
}
