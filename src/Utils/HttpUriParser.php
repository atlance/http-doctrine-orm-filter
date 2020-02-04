<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Utils;

class HttpUriParser
{
    public static function parse(string $uri, string $key = null): array
    {
        parse_str($uri, $args);
        array_walk_recursive(
            $args,
            function (&$val): void {
                $val = is_string($val) ? explode('|', $val) : [$val];
            }
        );

        if ($key === null) {
            return self::prepare($args);
        }

        if (array_key_exists($key, $args)) {
            return self::prepare($args[$key]);
        }

        return [];
    }

    private static function prepare(array $args): array
    {
        return json_decode((string) json_encode($args, JSON_NUMERIC_CHECK + JSON_PRESERVE_ZERO_FRACTION), true);
    }
}
