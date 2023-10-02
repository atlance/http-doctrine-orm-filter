<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "NOT LIKE()" expression with the given HTTP query arguments.
 */
final class NotLikeTest extends AbstractTestCase
{
    #[DataProvider('dataset')]
    public function test(string $uri, int $count): void
    {
        self::assertEquals($count, $this->fetch($uri));
    }

    /**
     * @return \Generator<array<int, string|int>>
     */
    public static function dataset(): \Generator
    {
        yield 'single: integer' => [self::NOT_LIKE . '[passport_sn]=676', 23];
        yield 'multiple: integer' => [self::NOT_LIKE . '[passport_sn]=676|303', 21];
        yield 'single: string' => [self::NOT_LIKE . '[users_email]=info', 3];
        yield 'multiple: string' => [self::NOT_LIKE . '[users_email]=info|shop', 2];
        yield 'single: boolean' => [self::NOT_LIKE . '[cards_available]=1', 17];
        yield 'single: datetime' => [self::NOT_LIKE . '[users_created_at]=2020-01', 15];
        yield 'multiple: datetime' => [self::NOT_LIKE . '[users_created_at]=2020-01|2019', 0];
    }
}
