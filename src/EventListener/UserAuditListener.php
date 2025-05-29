<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserAuditLog;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;

#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postFlush, priority: 500, connection: 'default')]
final class UserAuditListener
{
    /** @var UserAuditLog[] */
    private array $pendingLogs = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $reflectionClass = new ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if (in_array($property->getName(), ['id', 'deleted'])) {
                continue;
            }

            $methodName = 'get' . ucfirst($property->getName());
            if (method_exists($entity, $methodName)) {
                $rawValue = $entity->{$methodName}();
            } elseif (method_exists($entity, 'is' . ucfirst($property->getName()))) {
                $methodName = 'is' . ucfirst($property->getName());
                $rawValue = $entity->{$methodName}();
            } else {
                continue;
            }

            $formattedNewValue = $this->formatValue($rawValue);

            if ($formattedNewValue !== null) {
                $auditLog = new UserAuditLog(
                    $entity,
                    $property->getName(),
                    null,
                    $formattedNewValue
                );
                $this->pendingLogs[] = $auditLog;
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        $changeSet = $args->getEntityChangeSet();

        foreach ($changeSet as $fieldName => $values) {
            $rawOldValue = $values[0];
            $rawNewValue = $values[1];

            $formattedOldValue = $this->formatValue($rawOldValue);
            $formattedNewValue = $this->formatValue($rawNewValue);
            
            if ($formattedOldValue === $formattedNewValue) {
                continue;
            }

            $auditLog = new UserAuditLog(
                $entity,
                $fieldName,
                $formattedOldValue,
                $formattedNewValue
            );
            $this->pendingLogs[] = $auditLog;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (empty($this->pendingLogs)) {
            return;
        }

        foreach ($this->pendingLogs as $logEntry) {
            $this->entityManager->persist($logEntry);
        }
        
        $this->pendingLogs = [];
        
        $this->entityManager->flush();
    }

    private function formatValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s P');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || (is_object($value) && !method_exists($value, '__toString'))) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return $value === null ? null : (string) $value;
    }
} 