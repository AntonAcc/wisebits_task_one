<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\UserTest;

use App\Validator\Constraints\AllowedEmailDomains;
use App\Validator\Constraints\NotForbiddenWords;
use App\Validator\Constraints\UniqueUserFields;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;

class MockConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    private array $validators = [];
    private ConstraintValidatorFactoryInterface $defaultFactory;

    public function __construct()
    {
        $this->validators[UniqueUserFields::class] = new StubValidator();
        $this->validators[NotForbiddenWords::class] = new StubValidator();
        $this->validators[AllowedEmailDomains::class] = new StubValidator();
        $this->defaultFactory = new \Symfony\Component\Validator\ConstraintValidatorFactory();
    }

    public function getInstance(Constraint $constraint): ConstraintValidatorInterface
    {
        $className = get_class($constraint);
        return $this->validators[$className] ?? $this->defaultFactory->getInstance($constraint);
    }
} 