<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Exception;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "=" expression with the given HTTP query arguments.
 */
final class InvalidArgumentTest extends AbstractTestCase
{
    #[DataProvider('dataset')]
    public function test(string $uri): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->fetch($uri);
    }

    /**
     * @return \Generator<array<int, string|int>>
     */
    public static function dataset(): \Generator
    {
        yield 'not exist field name alias' => [self::EQ . '[cards_foo]=1&page=1'];
        yield 'not exist table name alias' => [self::EQ . '[foo_id]=1'];
        yield 'not exist expression' => ['filter[foo][users_id]=1'];
        yield 'not single value for ">=" expression' => [self::GTE . '[users_id]=1|2'];
        yield 'not single value for ">" expression' => [self::GT . '[users_id]=1|2'];
        yield 'not single value for "<" expression' => [self::LT . '[users_id]=1|2'];
        yield 'not single value for "=<" expression' => [self::LTE . '[users_id]=1|2'];
        yield 'single value for "IN()" function' => [self::IN . '[cards_available]=1'];
        yield 'single value for "NOT IN()" function' => [self::NOT_IN . '[cards_available]=1'];
        yield 'single value for "BETWEEN()" function' => [self::BETWEEN . '[users_id]=1'];
        yield 'supported only asc or desc' => ['order[users_id]=1'];
    }
}
