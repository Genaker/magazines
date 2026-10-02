<?php

namespace Tests\Unit;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Search\SearchPostContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPostContentTest extends TestCase
{
    use RefreshDatabase;

    private SearchPostContent $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content = app(SearchPostContent::class);
    }

    public function test_full_text_fields_truncates_body_to_configured_word_limit(): void
    {
        config([
            'search.post.fulltext.fields' => ['title', 'body'],
            'search.post.fulltext.body_max_words' => 5,
            'search.preprocessing.remove_stop_words.fulltext_index' => false,
        ]);

        $category = Category::query()->create(['name' => 'Tech', 'slug' => 'tech']);
        $words = array_merge(['alpha', 'beta', 'gamma', 'delta', 'epsilon'], ['zeta', 'eta', 'theta']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Title',
            'body' => '<p>'.implode(' ', $words).'</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        $fields = $this->content->fullTextFields($post);

        $this->assertSame('alpha beta gamma delta epsilon', $fields['body_text']);
        $this->assertStringNotContainsString('zeta', $fields['body_text']);
    }

    public function test_embedding_text_includes_only_configured_fields(): void
    {
        config([
            'search.post.embedding.fields' => ['title', 'tags'],
            'search.preprocessing.remove_stop_words.embedding' => false,
            'search.preprocessing.lowercase_for_embedding' => false,
        ]);

        $category = Category::query()->create(['name' => 'Culture', 'slug' => 'culture']);
        $tag = Tag::query()->create(['name' => 'redis', 'slug' => 'redis']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Embedding title',
            'subtitle' => 'Should be omitted',
            'body' => '<p>Body should be omitted</p>',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);
        $post->tags()->attach($tag);

        $text = $this->content->embeddingText($post);

        $this->assertStringContainsString('Embedding title', $text);
        $this->assertStringContainsString('redis', $text);
        $this->assertStringNotContainsString('Should be omitted', $text);
        $this->assertStringNotContainsString('Body should be omitted', $text);
    }

    public function test_full_text_query_clauses_respect_enabled_fields(): void
    {
        config(['search.post.fulltext.fields' => ['title', 'body']]);

        $clauses = $this->content->fullTextQueryFieldClauses('redis*');

        $this->assertContains('@title:(redis*)', $clauses);
        $this->assertContains('@body_text:(redis*)', $clauses);
        $this->assertNotContains('@author_name:(redis*)', $clauses);
        $this->assertNotContains('@category_name:(redis*)', $clauses);
    }

    public function test_full_text_fields_include_category_and_tags_when_enabled(): void
    {
        config(['search.post.fulltext.fields' => ['category_name', 'tags']]);

        $category = Category::query()->create(['name' => 'Science', 'slug' => 'science']);
        $tag = Tag::query()->create(['name' => 'physics', 'slug' => 'physics']);
        $post = Post::factory()->create([
            'category_id' => $category->id,
            'title' => 'Ignored title',
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);
        $post->tags()->attach($tag);

        $fields = $this->content->fullTextFields($post);

        $this->assertSame('Science', $fields['category_name']);
        $this->assertSame('physics', $fields['tag_slugs']);
        $this->assertSame('', $fields['title']);
    }
}
