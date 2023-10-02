<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\DQL;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use Atlance\HttpDoctrineOrmFilter\Test\Factory\RequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;

final class OrderByTest extends AbstractTestCase
{
    #[DataProvider('dataset')]
    public function test(string $uri, string $expectedDQL): void
    {
        $dql = $this->buildOrmQuery(RequestFactory::create($uri))->getDQL();

        $this->assertTrue(false !== mb_strpos($dql, $expectedDQL));
    }

    /**
     * @return \Generator<array<string>>
     */
    public static function dataset(): \Generator
    {
        yield ['order[cards_expires_at]=asc', 'ORDER BY cards.expiresAt'];
    }
}
