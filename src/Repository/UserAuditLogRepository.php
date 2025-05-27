<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAuditLog>
 *
 * @method UserAuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAuditLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAuditLog[]    findAll()
 * @method UserAuditLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class UserAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAuditLog::class);
    }

    public function save(UserAuditLog $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }
} 