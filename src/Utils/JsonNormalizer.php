<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Utils;

final class JsonNormalizer
{
    /**
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return array<string,mixed>
     *
     * @throws \JsonException
     */
    public static function normalize(
        array $data,
        int $flags = \JSON_THROW_ON_ERROR | \JSON_NUMERIC_CHECK + \JSON_PRESERVE_ZERO_FRACTION
    ): array {
        return (array) json_decode((string) json_encode($data, $flags), true, 512, \JSON_THROW_ON_ERROR);
    }
}
