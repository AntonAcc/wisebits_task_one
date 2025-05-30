<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\Validator\Constraints\NotForbiddenWords;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use ApiPlatform\Metadata\Delete;
use App\State\UserDeleteProcessor;
use App\Validator\Constraints\UniqueUserFields;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(columns: ['name', 'deleted'])]
#[ORM\UniqueConstraint(columns: ['email', 'deleted'])]
#[ApiResource(
     operations: [
         new GetCollection(),
         new Get(),
         new Post(),
         new Patch(),
         new Delete(processor: UserDeleteProcessor::class),
     ]
)]
#[UniqueUserFields]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 64)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+$/')]
    #[NotForbiddenWords]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 256)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 256)]
    #[Assert\Email]
    // TODO: Add validation for untrusted domains
    private string $email;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deleted = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct(string $name, string $email, ?string $notes = null)
    {
        $this->name = $name;
        $this->email = $email;
        $this->created = new \DateTimeImmutable();
        $this->notes = $notes;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getDeleted(): ?\DateTimeImmutable
    {
        return $this->deleted;
    }

    public function setDeleted(): void
    {
        $this->deleted = new \DateTimeImmutable();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    #[Assert\Callback]
    public function validateDeletedDate(ExecutionContextInterface $context, mixed $payload): void
    {
        if ($this->deleted !== null && $this->created->getTimestamp() > $this->deleted->getTimestamp()) {
            $context->buildViolation('Deletion date cannot be before creation date.')
                ->atPath('deleted')
                ->addViolation();
        }
    }
}
