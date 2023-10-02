<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test an instance of BETWEEN() function, with the given HTTP query arguments.
 */
final class BetweenTest extends AbstractTestCase
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
        yield 'integer' => [self::BETWEEN . '[users_id]=1|3', 3];
        yield 'date' => [self::BETWEEN . '[cards_expires_at]=2020-03-04|2020-07-08', 3];
        yield 'datetime' => [self::BETWEEN . '[users_created_at]=2019-12-20 21:34:30|2020-01-07 00:21:03', 5];
    }
}
