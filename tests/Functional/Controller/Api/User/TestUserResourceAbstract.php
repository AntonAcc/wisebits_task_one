<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client as ApiTestClient;
use App\Entity\User;
use App\Entity\UserAuditLog;
use App\Repository\UserAuditLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

abstract class TestUserResourceAbstract extends ApiTestCase
{
    protected ?EntityManagerInterface $entityManager;
    protected ?UserRepository $userRepository;
    protected ?UserAuditLogRepository $userAuditLogRepository;
    protected ?ApiTestClient $apiTestClient;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->userAuditLogRepository = $container->get(UserAuditLogRepository::class);

        $this->truncateEntities([User::class, UserAuditLog::class]);

        $this->apiTestClient = static::createClient();
    }

    protected function truncateEntities(array $entityClasses): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        foreach ($entityClasses as $entityClass) {
            $cmd = $this->entityManager->getClassMetadata($entityClass);
            $connection->executeStatement($platform->getTruncateTableSQL($cmd->getTableName(), true /* CASCADE */));
        }
    }

    protected function tearDown(): void
    {
        $this->apiTestClient = null;

        if ($this->entityManager !== null) {
            $this->entityManager->clear();
            $this->entityManager = null;
        }

        $this->userRepository = null;
        $this->userAuditLogRepository = null;

        parent::tearDown();
    }

    protected function disableFilters(): void
    {
        $this->entityManager->getFilters()->disable('soft_deleteable');
    }

    protected function enableFilters(): void
    {
        $this->entityManager->getFilters()->enable('soft_deleteable');
    }

    protected function getFreshUserRepository(): UserRepository
    {
        return static::getContainer()->get(UserRepository::class);
    }
}
