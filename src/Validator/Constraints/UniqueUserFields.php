<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueUserFields extends Constraint
{
    public string $emailMessage = 'This email is already used.';
    public string $nameMessage = 'This username is already taken.';

   public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}