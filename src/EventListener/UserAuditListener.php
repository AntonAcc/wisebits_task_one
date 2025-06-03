<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Entity\UserAuditLog;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use ReflectionClass;

#[AsDoctrineListener(event: Events::onFlush, priority: 500, connection: 'default')]
final class UserAuditListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof User) {
                continue;
            }

            $reflection = new ReflectionClass($entity);
            foreach ($reflection->getProperties() as $property) {
                if (in_array($property->getName(), ['id', 'deleted'], true)) {
                    continue;
                }

                $getter = 'get' . ucfirst($property->getName());
                if (!method_exists($entity, $getter)) {
                    $getter = 'is' . ucfirst($property->getName());
                    if (!method_exists($entity, $getter)) {
                        continue;
                    }
                }

                $newValue = $this->formatValue($entity->$getter());

                if ($newValue !== null) {
                    $log = new UserAuditLog($entity, $property->getName(), null, $newValue);
                    $em->persist($log);
                    $uow->computeChangeSet($em->getClassMetadata(UserAuditLog::class), $log);
                }
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof User) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            foreach ($changeSet as $field => [$oldValue, $newValue]) {
                $oldFormatted = $this->formatValue($oldValue);
                $newFormatted = $this->formatValue($newValue);

                if ($oldFormatted === $newFormatted) {
                    continue;
                }

                $log = new UserAuditLog($entity, $field, $oldFormatted, $newFormatted);
                $em->persist($log);
                $uow->computeChangeSet($em->getClassMetadata(UserAuditLog::class), $log);
            }
        }
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