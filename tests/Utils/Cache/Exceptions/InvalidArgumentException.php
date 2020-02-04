<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Test\Utils\Cache\Exceptions;

class InvalidArgumentException extends \Exception implements \Psr\SimpleCache\InvalidArgumentException
{
}
