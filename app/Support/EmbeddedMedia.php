<?php

namespace App\Support;

/** Normalizes embedded iframe/video markup for responsive layouts. */
class EmbeddedMedia
{
    /** Strip fixed dimensions so CSS can size embeds to the content width. */
    public static function normalize(string $html): string
    {
        if (! preg_match('/<(iframe|video|embed|object)\b/i', $html)) {
            return $html;
        }

        return preg_replace_callback(
            '/<(iframe|video|embed|object)\b([^>]*)>/i',
            function (array $matches): string {
                $tag = $matches[1];
                $attrs = preg_replace('/\s*(width|height)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $matches[2]) ?? '';
                $attrs = preg_replace('/\s*style\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $attrs) ?? '';

                return '<'.$tag.trim($attrs).'>';
            },
            $html,
        );
    }
}
