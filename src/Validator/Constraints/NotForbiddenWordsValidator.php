<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Service\ForbiddenWordsServiceInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NotForbiddenWordsValidator extends ConstraintValidator
{
    public function __construct(
        private ForbiddenWordsServiceInterface $forbiddenWordsService
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotForbiddenWords) {
            throw new UnexpectedTypeException($constraint, NotForbiddenWords::class);
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $containsForbidden = $this->forbiddenWordsService->check($value);
        if ($containsForbidden) {
            $this->context->buildViolation('This username contains some forbidden words')
                ->atPath('name')
                ->addViolation();
        }
    }
}