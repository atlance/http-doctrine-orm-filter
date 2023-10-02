<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\Exception;

use Atlance\HttpDoctrineOrmFilter\Test\Acceptance\Filter\AbstractTestCase;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\SimpleCache\InvalidArgumentException as PsrException;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Test a "=" expression with the given HTTP query arguments.
 */
final class ValidatorExceptionTest extends AbstractTestCase
{
    #[DataProvider('dataset')]
    public function test(string $uri, int $expectedCountViolation): void
    {
        try {
            $this->fetch($uri);
        } catch (ValidatorException $e) {
            /** @var array $errors */
            $errors = json_decode($e->getMessage(), true);

            self::assertCount($expectedCountViolation, $errors);
        } catch (NoResultException | NonUniqueResultException | InvalidArgumentException | PsrException) {
        }
    }

    /**
     * @return \Generator<array<int, string|int>>
     */
    public static function dataset(): \Generator
    {
        yield 'single: expected integer, string given' => [self::EQ . '[users_id]=aww', 1];
        yield 'multiple: expected integer, string given' => [self::EQ . '[users_id]=a|b', 1];
        yield 'not valid number, email, id' => [
            self::EQ . '[phones_number]=foo&'
            . self::EQ . '[users_email]=info&'
            . self::EQ . '[users_id]=bar',
            3,
        ];
    }
}
