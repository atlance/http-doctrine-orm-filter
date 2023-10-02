<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "!=" expression with the given HTTP query arguments.
 */
final class NotEqualTest extends AbstractTestCase
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
        yield 'single: integer' => [self::NEQ . '[passport_sn]=6762843688', 24];
        yield 'multiple: integer' => [self::NEQ . '[passport_sn]=4794840291|6761458394', 23];
        yield 'single: string' => [self::NEQ . '[cards_bank_name]=Ермак', 23];
        yield 'multiple: string' => [self::NEQ . '[cards_bank_name]=Союз|Ермак', 21];
        yield 'single: boolean' => [self::NEQ . '[cards_available]=1', 17];
        yield 'single: datetime' => [self::NEQ . '[users_created_at]=2019-12-04 07:21:44', 24];
        yield 'multiple: datetime' => [self::NEQ . '[users_created_at]=2019-12-04 07:21:44|2019-12-04 23:19:41', 23];
    }
}
