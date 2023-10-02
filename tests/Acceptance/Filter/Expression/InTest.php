<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "IN()" expression with the given HTTP query arguments.
 */
final class InTest extends AbstractTestCase
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
        yield 'integer' => [self::IN . '[passport_sn]=4794840291|6761458394', 2];
        yield 'string' => [self::IN . '[cards_bank_name]=Союз|Ермак', 4];
        yield 'datetime' => [self::IN . '[users_created_at]=2019-12-04 07:21:44|2019-12-04 23:19:41', 2];
    }
}
