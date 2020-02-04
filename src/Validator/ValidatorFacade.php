<?php

declare(strict_types=1);

namespace Atlance\HttpDoctrineFilter\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidatorFacade
{
    /** @var ValidatorInterface */
    private $validator;
    /** @var array */
    private $groups;
    /** @var array */
    private $buffer;

    public function __construct(ValidatorInterface $validator, array $groups = [])
    {
        $this->validator = $validator;
        $this->groups = $groups;
        $this->buffer = [];
    }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    public function setValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    public function validatePropertyValue(string $key, string $className, string $fieldName, array $fieldValues): array
    {
        $violations = [];
        foreach ($fieldValues as $fieldValue) {
            $violationList = $this->validator->validatePropertyValue($className, $fieldName, $fieldValue, $this->groups);
            if ($violationList->count() > 0) {
                if (!array_key_exists($key, $violations)) {
                    $violations[$key] = [];
                }

                array_push($violations[$key], $this->getViolations($violationList, $key));
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
        return count($this->buffer) === 0;
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

            array_push($violations, $violationEntry);
        }

        $this->setBufferViolationsByKey($key, $violations);

        return $violations;
    }

    private function getBufferViolationsByKey(string $key): array
    {
        return array_key_exists($key, $this->buffer) ? $this->buffer[$key] : [];
    }

    private function setBufferViolationsByKey(string $key, array $violations): void
    {
        $this->buffer[$key] = $violations;
    }
}
