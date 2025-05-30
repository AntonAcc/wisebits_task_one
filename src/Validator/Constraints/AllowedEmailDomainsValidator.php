<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Service\ForbiddenWordsServiceInterface;
use App\Service\NotAllowedEmailDomainsServiceInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class AllowedEmailDomainsValidator extends ConstraintValidator
{
    public function __construct(
        private NotAllowedEmailDomainsServiceInterface $notAllowedEmailDomainsService
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof AllowedEmailDomains) {
            throw new UnexpectedTypeException($constraint, AllowedEmailDomains::class);
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $isNotAllowedEmailDomain = $this->notAllowedEmailDomainsService->check($value);
        if ($isNotAllowedEmailDomain) {
            $this->context->buildViolation('This email domain is not allowed.')
                ->atPath('email')
                ->addViolation();
        }
    }
}