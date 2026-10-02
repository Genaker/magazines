<?php

namespace App\Services\Embeddings;

use App\Contracts\Embeddings\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** OpenAI-compatible embeddings API (OpenAI, Azure, local gateways). */
class OpenAiEmbeddingProvider implements EmbeddingProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return filled(config('search.embeddings.openai.api_key'));
    }

    public function dimensions(): int
    {
        $configured = config('search.embeddings.openai.dimensions');

        return $configured !== null && $configured !== ''
            ? (int) $configured
            : (int) config('search.embeddings.dimensions', 1536);
    }

    public function embed(string $text): array
    {
        $apiKey = (string) config('search.embeddings.openai.api_key');
        $model = (string) config('search.embeddings.openai.model', 'text-embedding-3-small');
        $baseUrl = rtrim((string) config('search.embeddings.openai.base_url'), '/');

        $payload = [
            'model' => $model,
            'input' => $text,
        ];

        if (config('search.embeddings.openai.dimensions')) {
            $payload['dimensions'] = (int) config('search.embeddings.openai.dimensions');
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->withToken($apiKey)
            ->post("{$baseUrl}/embeddings", $payload);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI embedding request failed: '.$response->body());
        }

        $values = $response->json('data.0.embedding');

        if (! is_array($values) || $values === []) {
            throw new RuntimeException('OpenAI embedding response missing values.');
        }

        return array_map('floatval', $values);
    }
}
