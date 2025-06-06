<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Dto\Input\UserCreateDto;
use App\Dto\Input\UserUpdateDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueUserFieldsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserFields) {
            throw new UnexpectedTypeException($constraint, UniqueUserFields::class);
        }

        if (!$value instanceof UserCreateDto) {
            throw new UnexpectedValueException($value, UserCreateDto::class);
        }

        $repo = $this->em->getRepository(User::class);

        $conflictEmail = $repo->findOneBy(['email' => $value->email]);
        if ($conflictEmail) {
            $this->context->buildViolation($constraint->emailMessage)
                ->atPath('email')
                ->addViolation();
        }

        $conflictName = $repo->findOneBy(['name' => $value->name]);
        if ($conflictName) {
            $this->context->buildViolation($constraint->nameMessage)
                ->atPath('name')
                ->addViolation();
        }
    }
}