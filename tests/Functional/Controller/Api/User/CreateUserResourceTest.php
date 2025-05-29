<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Repository\UserAuditLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use DateTimeImmutable;

final class CreateUserResourceTest extends ApiTestCase
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

        // Очищаем таблицу пользователей перед каждым функциональным тестом
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
            $connection->executeStatement($platform->getTruncateTableSQL($cmd->getTableName(), true /* CASCADE */));
        }
    }

    protected function tearDown(): void
    {
        // Ensure to clear the entity manager to avoid state leakage between tests
        if ($this->entityManager !== null) {
            $this->entityManager->clear();
            $this->entityManager = null; // avoid memory leaks
        }
        
        $this->userRepository = null; // avoid memory leaks
        $this->userAuditLogRepository = null; // avoid memory leaks
        
        parent::tearDown();
    }

    public function testCreateUser(): void
    {
        $client = static::createClient();

        $userData = [
            'name' => 'testusercreate',
            'email' => 'test.user.create@example.com',
            'notes' => 'Initial notes for creation test.',
        ];

        $response = $client->request('POST', '/api/users', [
            'json' => $userData,
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $responseData = $response->toArray();

        $this->assertArrayHasKey('id', $responseData);
        $this->assertIsInt($responseData['id']);
        $userId = $responseData['id'];

        $this->assertSame($userData['name'], $responseData['name']);
        $this->assertSame($userData['email'], $responseData['email']);
        $this->assertSame($userData['notes'], $responseData['notes']);

        $this->assertArrayHasKey('created', $responseData);
        $this->assertNotNull($responseData['created']);
        $this->assertIsString($responseData['created']);

        $this->assertArrayHasKey('deleted', $responseData);
        $this->assertNull($responseData['deleted']);

        /** @var User|null $dbUser */
        $dbUser = $this->userRepository->find($userId);
        $this->assertNotNull($dbUser);
        $this->assertSame($userData['name'], $dbUser->getName());
        $this->assertSame($userData['email'], $dbUser->getEmail());
        $this->assertSame($userData['notes'], $dbUser->getNotes());
        $this->assertNotNull($dbUser->getCreated());
        $this->assertNull($dbUser->getDeleted());

        $auditLogs = $this->userAuditLogRepository->findBy(['user' => $dbUser], ['changedAt' => 'ASC']);
        
        $expectedAuditFields = ['name', 'email', 'created'];
        if (!empty($userData['notes'])) {
            $expectedAuditFields[] = 'notes';
             $this->assertCount(4, $auditLogs); 
        } else {
            $this->assertCount(3, $auditLogs);
        }
        
        $loggedFields = [];
        foreach ($auditLogs as $log) {
            $loggedFields[] = $log->getFieldName();
            $this->assertNull($log->getOldValue());
            $this->assertNotNull($log->getNewValue());
            $this->assertSame($dbUser->getId(), $log->getUser()->getId());

            if ($log->getFieldName() === 'name') {
                $this->assertSame($userData['name'], $log->getNewValue());
            } elseif ($log->getFieldName() === 'email') {
                $this->assertSame($userData['email'], $log->getNewValue());
            } elseif ($log->getFieldName() === 'created') {
                $this->assertSame($dbUser->getCreated()->format('Y-m-d H:i:s P'), DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u P', $log->getNewValue())->format('Y-m-d H:i:s P'));
            } elseif ($log->getFieldName() === 'notes') {
                 $this->assertSame($userData['notes'], $log->getNewValue());
            }
        }
        
        foreach ($expectedAuditFields as $expectedField) {
            $this->assertContains($expectedField, $loggedFields, "Audit log for field '{$expectedField}' is missing.");
        }
    }
} 