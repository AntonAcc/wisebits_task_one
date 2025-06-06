<?php

declare(strict_types=1);

namespace App\Entity;

class User
{
    private ?int $id = null;

    private string $name;

    private string $email;

    private \DateTimeImmutable $created;

    private ?\DateTimeImmutable $deleted = null;

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
}
