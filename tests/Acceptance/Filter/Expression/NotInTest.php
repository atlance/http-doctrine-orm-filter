<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "NOT IN()" expression with the given HTTP query arguments.
 */
final class NotInTest extends AbstractTestCase
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
        yield 'integer' => [self::NOT_IN . '[passport_sn]=4794840291|6761458394', 23];
        yield 'string' => [self::NOT_IN . '[cards_bank_name]=Союз|Ермак', 21];
        yield 'datetime' => [self::NOT_IN . '[users_created_at]=2019-12-04 07:21:44|2019-12-04 23:19:41', 23];
    }
}
