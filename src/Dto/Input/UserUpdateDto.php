<?php

declare(strict_types=1);

namespace App\Dto\Input;

use App\Validator\Constraints\AllowedEmailDomains;
use App\Validator\Constraints\NotForbiddenWords;
use Symfony\Component\Validator\Constraints as Assert;

class UserUpdateDto
{
    #[Assert\Length(min: 8, max: 64)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+$/',
        message: 'Name can only contain lowercase letters and numbers.',
    )]
    #[NotForbiddenWords]
    public ?string $name = null;

    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(max: 256)]
    #[Assert\Email]
    #[AllowedEmailDomains]
    public ?string $email = null;

    public ?string $notes = null;
}