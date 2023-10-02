<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "LOWER() LIKE()" expression with the given HTTP query arguments.
 */
final class LowerLikeTest extends AbstractTestCase
{
    #[DataProvider('dataset')]
    public function test(string $uri, int $count): void
    {
        $this->assertCountByHttpQuery($uri, $count);
    }

    /**
     * @return \Generator<array<int, string|int>>
     */
    public static function dataset(): \Generator
    {
        yield 'single: integer' => [self::ILIKE . '[passport_sn]=676', 2];
        yield 'multiple: integer' => [self::ILIKE . '[passport_sn]=676|303', 4];
        yield 'single: string' => [self::ILIKE . '[users_email]=info', 2];
        yield 'multiple: string' => [self::ILIKE . '[users_email]=info|shop', 3];
        yield 'single: boolean' => [self::ILIKE . '[cards_available]=1', 8];
        yield 'single: datetime' => [self::ILIKE . '[users_created_at]=2020-01', 10];
        yield 'multiple: datetime' => [self::ILIKE . '[users_created_at]=2020-01|2019', 25];
    }
}
