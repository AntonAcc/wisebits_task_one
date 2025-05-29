<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Repository\UserAuditLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class GetUserResourceTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserRepository $userRepository;
    private ?UserAuditLogRepository $userAuditLogRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->userAuditLogRepository = $container->get(UserAuditLogRepository::class);

        $this->truncateEntities([User::class, \App\Entity\UserAuditLog::class]);
    }

    private function truncateEntities(array $entityClasses): void
    {
        if (!$this->entityManager) {
            return;
        }
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        foreach ($entityClasses as $entityClass) {
            $cmd = $this->entityManager->getClassMetadata($entityClass);
            $connection->executeStatement($platform->getTruncateTableSQL($cmd->getTableName(), true));
        }
    }

    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            $this->entityManager->clear();
            $this->entityManager = null;
        }
        $this->userRepository = null;
        $this->userAuditLogRepository = null;
        parent::tearDown();
    }

    public function testGetUser(): void
    {
        $client = static::createClient();

        $name = 'gettestuser';
        $email = 'get.test.user@example.com';
        $notes = 'Some notes for get test.';
        
        $user = new User($name, $email, $notes);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $userId = $user->getId();
        $createdDate = $user->getCreated();
        $this->entityManager->clear();

        // Clear audit logs from creation before making the GET request
        $this->truncateEntities([\App\Entity\UserAuditLog::class]);

        $response = $client->request('GET', '/api/users/' . $userId);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        
        $responseData = $response->toArray();

        $this->assertSame($userId, $responseData['id']);
        $this->assertSame($name, $responseData['name']);
        $this->assertSame($email, $responseData['email']);
        $this->assertSame($notes, $responseData['notes']);
        $this->assertNotNull($responseData['created']);
        $this->assertSame($createdDate->format('Y-m-d H:i:s P'), (new \DateTimeImmutable($responseData['created']))->format('Y-m-d H:i:s P'));
        $this->assertArrayHasKey('deleted', $responseData, "The 'deleted' key should exist in the response.");
        $this->assertNull($responseData['deleted'], "The 'deleted' field should be null for a new user.");

        // Verify no audit logs were created for a GET request
        $auditLogs = $this->userAuditLogRepository->findBy(['user' => $userId]);
        $this->assertCount(0, $auditLogs, 'No audit logs should be created for a GET request.');
    }
} 