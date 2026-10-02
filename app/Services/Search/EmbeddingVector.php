<?php

namespace App\Services\Search;

/** Pack float vectors for RediSearch VECTOR fields. */
class EmbeddingVector
{
    /** @param  list<float>  $values */
    public static function pack(array $values): string
    {
        return pack('f*', ...$values);
    }

    /** @return list<float> */
    public static function unpack(string $bytes, int $dimensions): array
    {
        $values = unpack('f*', $bytes);

        return $values === false ? [] : array_values($values);
    }
}
