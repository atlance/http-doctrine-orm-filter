<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Expression;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test a "=" expression with the given HTTP query arguments.
 */
final class EqualTest extends AbstractTestCase
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
        yield 'single: integer' => [self::EQ . '[users_id]=1', 1];
        yield 'multiple: integer & string' => [
            self::EQ . '[users_id]=7&' . self::EQ . '[users_email]=tmed@zvnhkcpnq.shop',
            1,
        ];
        yield 'multiple: integer & boolean & float' => [
            self::EQ . '[users_id]=1|2|3&' . self::EQ . '[cards_available]=1&' . self::EQ . '[cards_balance]=24760.21',
            1,
        ];
        yield 'single: float' => [self::EQ . '[cards_balance]=24760.21', 1];
        yield 'multiple: float' => [self::EQ . '[cards_balance]=112825122.79|394952707.36', 2];
        yield 'single: string' => [self::EQ . '[cards_bank_name]=Интеркоммерц Банк', 1];
        yield 'multiple: string' => [self::EQ . '[cards_bank_name]=Интеркоммерц Банк|Ермак', 3];
        yield 'single: boolean true' => [self::EQ . '[cards_available]=1', 8];
        yield 'single: boolean false' => [self::EQ . '[cards_available]=0', 17];
        yield 'single: datetime' => [self::EQ . '[users_created_at]=2019-12-04 07:21:44', 1];
        yield 'multiple: datetime' => [self::EQ . '[users_created_at]=2019-12-04 07:21:44|2019-12-04 23:19:41', 2];
    }
}
