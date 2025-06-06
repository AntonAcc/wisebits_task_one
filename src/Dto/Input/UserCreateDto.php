<?php

declare(strict_types=1);

namespace App\Dto\Input;

use App\Validator\Constraints\AllowedEmailDomains;
use App\Validator\Constraints\NotForbiddenWords;
use App\Validator\Constraints\UniqueUserFields;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueUserFields]
class UserCreateDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 64)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+$/',
        message: 'Name can only contain lowercase letters and numbers.'
    )]
    #[NotForbiddenWords]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(max: 256)]
    #[Assert\Email]
    #[AllowedEmailDomains]
    public string $email;

    public ?string $notes = null;

    // Конструктор можно добавить, если это упростит создание DTO,
    // но для API Platform он обычно не требуется, т.к. десериализатор заполняет публичные свойства.
    // public function __construct(string $name, string $email, ?string $notes = null)
    // {
    //     $this->name = $name;
    //     $this->email = $email;
    //     $this->notes = $notes;
    // }
} 