<?php

namespace Tests\Unit;

use App\Services\Embeddings\EmbeddingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmbeddingServiceTest extends TestCase
{
    public function test_uses_null_provider_by_default(): void
    {
        config([
            'search.embeddings.provider' => 'none',
        ]);

        $service = app(EmbeddingService::class);

        $this->assertSame('none', $service->provider()->name());
        $this->assertFalse($service->semanticEnabled());
        $this->assertSame([], $service->embedQuery('hello'));
    }

    public function test_gemini_provider_returns_embedding_values(): void
    {
        config([
            'search.embeddings.provider' => 'gemini',
            'search.embeddings.gemini.api_key' => 'test-key',
            'search.embeddings.gemini.model' => 'text-embedding-004',
            'search.semantic.enabled' => true,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => [0.1, 0.2, 0.3]],
            ]),
        ]);

        $service = app(EmbeddingService::class);

        $this->assertSame([0.1, 0.2, 0.3], $service->embedForIndex('sample text'));
    }

    public function test_openai_provider_is_selectable(): void
    {
        config([
            'search.embeddings.provider' => 'openai',
            'search.embeddings.openai.api_key' => 'test-key',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [['embedding' => [0.5, 0.6]]],
            ]),
        ]);

        $provider = app(EmbeddingService::class)->provider();

        $this->assertSame('openai', $provider->name());
        $this->assertSame([0.5, 0.6], $provider->embed('hello world'));
    }
}
