<?php

declare(strict_types=1);

namespace App\Entity;

class UserAuditLog
{
    private ?int $id = null;

    private User $user;

    private string $fieldName;

    private ?string $oldValue = null;

    private ?string $newValue = null;

    private \DateTimeImmutable $changedAt;

    public function __construct(
        User $user,
        string $fieldName,
        ?string $oldValue,
        ?string $newValue
    ) {
        $this->user = $user;
        $this->fieldName = $fieldName;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function getNewValue(): ?string
    {
        return $this->newValue;
    }

    public function getChangedAt(): \DateTimeImmutable
    {
        return $this->changedAt;
    }
} 