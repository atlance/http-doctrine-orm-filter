<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineOrmFilter\Query;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @psalm-suppress MixedArgumentTypeCoercion
 * @psalm-suppress MixedInferredReturnType
 * @psalm-suppress MixedReturnStatement
 */
final class Validator
{
    private array $buffer;

    public function __construct(private ValidatorInterface $validator, private array $groups = [])
    {
        $this->buffer = [];
    }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function validatePropertyValue(string $key, string $className, string $fieldName, array $fieldValues): array
    {
        $violations = [];
        /** @var mixed $fieldValue */
        foreach ($fieldValues as $fieldValue) {
            $violationList = $this->validator->validatePropertyValue($className, $fieldName, $fieldValue, $this->groups);
            if ($violationList->count() > 0) {
                if (!\array_key_exists($key, $violations)) {
                    $violations[$key] = [];
                }

                $violations[$key][] = $this->getViolations($violationList, $key);
            }
        }

        return $violations;
    }

    public function getAllViolations(): array
    {
        return $this->buffer;
    }

    public function isValid(): bool
    {
        return 0 === \count($this->buffer);
    }

    private function getViolations(ConstraintViolationListInterface $violationList, string $key): array
    {
        $violations = $this->getBufferViolationsByKey($key);

        /** @var ConstraintViolation $violation */
        foreach ($violationList as $violation) {
            $violationEntry = [
                'title' => $violation->getMessage(),
                'queryPath' => $key,
                'value' => $violation->getInvalidValue(),
            ];

            $violations[] = $violationEntry;
        }

        $this->setBufferViolationsByKey($key, $violations);

        return $violations;
    }

    /**
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    private function getBufferViolationsByKey(string $key): array
    {
        return \array_key_exists($key, $this->buffer) ? $this->buffer[$key] : [];
    }

    private function setBufferViolationsByKey(string $key, array $violations): void
    {
        $this->buffer[$key] = $violations;
    }
}
