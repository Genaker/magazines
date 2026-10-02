<?php

namespace App\Support;

/** Normalizes and trims text for RediSearch and embedding APIs. */
class SearchTextPreprocessor
{
    /** Strip HTML and collapse whitespace. */
    public function plainText(string $text): string
    {
        if (config('search.preprocessing.strip_html', true)) {
            $text = strip_tags($text);
        }

        if (config('search.preprocessing.collapse_whitespace', true)) {
            $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        }

        return $text;
    }

    /** Keep the first N words (0 = unlimited). */
    public function limitWords(string $text, int $maxWords): string
    {
        if ($maxWords <= 0 || $text === '') {
            return $text;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false || count($words) <= $maxWords) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $maxWords));
    }

    /** Remove configured stop words (whole-word, case-insensitive). */
    public function removeStopWords(string $text): string
    {
        $stopWords = array_flip(array_map(
            'mb_strtolower',
            config('search.preprocessing.stop_words', []),
        ));

        if ($stopWords === []) {
            return $text;
        }

        $minLength = (int) config('search.preprocessing.min_token_length', 2);

        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $filtered = array_values(array_filter($tokens, function (string $token) use ($stopWords, $minLength) {
            $lower = mb_strtolower($token);

            if (mb_strlen($lower) < $minLength) {
                return false;
            }

            return ! isset($stopWords[$lower]);
        }));

        return implode(' ', $filtered);
    }

    /** Full-text index body/title values — light cleanup, optional stop-word removal. */
    public function forFullTextIndex(string $text, int $bodyMaxWords = 0): string
    {
        $text = $this->plainText($text);
        $text = $this->limitWords($text, $bodyMaxWords);

        if (config('search.preprocessing.remove_stop_words.fulltext_index', false)) {
            $text = $this->removeStopWords($text);
        }

        return $text;
    }

    /** Embedding input — aggressive token savings. */
    public function forEmbedding(string $text, int $bodyMaxWords = 0): string
    {
        $text = $this->plainText($text);
        $text = $this->limitWords($text, $bodyMaxWords);

        if (config('search.preprocessing.remove_stop_words.embedding', true)) {
            $text = $this->removeStopWords($text);
        }

        if (config('search.preprocessing.lowercase_for_embedding', true)) {
            $text = mb_strtolower($text);
        }

        return $text;
    }

    /** User search query before FT escape or embedding API. */
    public function forFullTextQuery(string $query): string
    {
        $query = $this->plainText($query);

        if (config('search.preprocessing.remove_stop_words.fulltext_query', true)) {
            $query = $this->removeStopWords($query);
        }

        return $query;
    }
}
