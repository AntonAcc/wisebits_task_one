<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserAuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserAuditLogRepository::class)]
#[ORM\Table(name: 'user_audit_logs')]
class UserAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $userId;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $fieldName;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $oldValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $newValue = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $changedAt;

    public function __construct(
        int $userId,
        string $fieldName,
        ?string $oldValue,
        ?string $newValue
    ) {
        $this->userId = $userId;
        $this->fieldName = $fieldName;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
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