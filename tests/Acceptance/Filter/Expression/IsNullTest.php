<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "IS NULL" expression with the given HTTP query arguments.
 */
final class IsNullTest extends AbstractTestCase
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
        yield 'single: integer' => [self::IS_NULL . '[users_id]', 0];
        yield 'single: string' => [self::IS_NULL . '[users_email]', 20];
        yield 'single: boolean' => [self::IS_NULL . '[cards_available]', 0];
    }
}
