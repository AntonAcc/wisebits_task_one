<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\UserTest;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MockUniqueUserFieldsValidator implements ConstraintValidatorInterface
{
    public function initialize(ExecutionContextInterface $context): void {}
    public function validate(mixed $value, Constraint $constraint): void {}
} 