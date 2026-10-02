<?php

namespace Tests\Unit;

use App\Support\SearchTextPreprocessor;
use Tests\TestCase;

class SearchTextPreprocessorTest extends TestCase
{
    private SearchTextPreprocessor $preprocessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preprocessor = app(SearchTextPreprocessor::class);
    }

    public function test_plain_text_strips_html_and_collapses_whitespace(): void
    {
        config(['search.preprocessing.strip_html' => true]);

        $result = $this->preprocessor->plainText("<p>Hello <strong>world</strong></p>\n\n  foo");

        $this->assertSame('Hello world foo', $result);
    }

    public function test_limit_words_truncates_to_first_n_words(): void
    {
        $text = 'one two three four five six';

        $this->assertSame('one two three', $this->preprocessor->limitWords($text, 3));
        $this->assertSame($text, $this->preprocessor->limitWords($text, 0));
    }

    public function test_remove_stop_words_filters_common_tokens(): void
    {
        config([
            'search.preprocessing.stop_words' => ['the', 'and', 'is'],
            'search.preprocessing.min_token_length' => 2,
        ]);

        $result = $this->preprocessor->removeStopWords('the quick brown fox is running');

        $this->assertSame('quick brown fox running', $result);
    }

    public function test_for_embedding_lowercases_and_removes_stop_words(): void
    {
        config([
            'search.preprocessing.remove_stop_words.embedding' => true,
            'search.preprocessing.lowercase_for_embedding' => true,
            'search.preprocessing.stop_words' => ['the', 'is'],
            'search.preprocessing.min_token_length' => 2,
        ]);

        $result = $this->preprocessor->forEmbedding('The Redis Stack IS great', 0);

        $this->assertSame('redis stack great', $result);
    }

    public function test_for_full_text_query_removes_stop_words_by_default(): void
    {
        config([
            'search.preprocessing.remove_stop_words.fulltext_query' => true,
            'search.preprocessing.stop_words' => ['the', 'of'],
            'search.preprocessing.min_token_length' => 2,
        ]);

        $result = $this->preprocessor->forFullTextQuery('the art of search');

        $this->assertSame('art search', $result);
    }

    public function test_for_full_text_index_applies_body_word_limit(): void
    {
        config(['search.preprocessing.remove_stop_words.fulltext_index' => false]);

        $body = implode(' ', range(1, 150));

        $result = $this->preprocessor->forFullTextIndex($body, 100);

        $this->assertSame(implode(' ', range(1, 100)), $result);
    }
}
