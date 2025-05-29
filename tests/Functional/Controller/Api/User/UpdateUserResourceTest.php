<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Repository\UserAuditLogRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class UpdateUserResourceTest extends ApiTestCase
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

    public function testUpdateUser(): void
    {
        $client = static::createClient();

        $initialName = 'originaluser';
        $initialEmail = 'original.user@example.com';
        $initialNotes = 'Initial notes.';
        
        $user = new User($initialName, $initialEmail, $initialNotes);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var User $userToUpdate */
        $userToUpdate = $this->userRepository->findOneBy(['email' => $initialEmail]); 
        $this->assertNotNull($userToUpdate, 'Failed to create user for update test.');
        $userId = $userToUpdate->getId();

        $this->truncateEntities([\App\Entity\UserAuditLog::class]);

        $updatedName = 'updateduser';
        $updatedNotes = 'Updated notes.';
        $updateData = [
            'name' => $updatedName,
            'notes' => $updatedNotes,
        ];

        $response = $client->request('PATCH', '/api/users/' . $userId, [
            'json' => $updateData,
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK, 'PATCH request itself failed. Response: ' . $response->getContent());
        $responseData = $response->toArray();

        $this->assertSame($updatedName, $responseData['name']);
        $this->assertSame($initialEmail, $responseData['email']); 
        $this->assertSame($updatedNotes, $responseData['notes']);
        $this->assertNotNull($responseData['created']);
        $this->assertNull($responseData['deleted']);

        $freshEntityManager = static::getContainer()->get(EntityManagerInterface::class);
        /** @var UserRepository $freshUserRepository */
        $freshUserRepository = static::getContainer()->get(UserRepository::class);

        /** @var User|null $dbUser */
        $dbUser = $freshUserRepository->find($userId);
        $this->assertNotNull($dbUser, 'User not found in DB with fresh EM.');
        
        $freshEntityManager->refresh($dbUser);

        $this->assertSame($updatedName, $dbUser->getName(), 'Name in DB was not updated.');
        $this->assertSame($initialEmail, $dbUser->getEmail(), 'Email in DB should not have changed.');
        $this->assertSame($updatedNotes, $dbUser->getNotes(), 'Notes in DB was not updated.');
        $this->assertNotNull($dbUser->getCreated(), 'Created date missing in DB User.');
        $this->assertNull($dbUser->getDeleted(), 'Deleted date should be null in DB User.');

        $this->assertSame($updatedName, $dbUser->getName(), 'Name for log checking is not updated (pre-log check).');

        $auditLogs = $this->userAuditLogRepository->findBy(['user' => $dbUser], ['changedAt' => 'ASC']);
        $this->assertCount(2, $auditLogs, 'Expected 2 audit logs for name and notes update.');

        $expectedChanges = [
            'name' => ['old' => $initialName, 'new' => $updatedName],
            'notes' => ['old' => $initialNotes, 'new' => $updatedNotes],
        ];

        foreach ($auditLogs as $log) {
            $fieldName = $log->getFieldName();
            $this->assertArrayHasKey($fieldName, $expectedChanges, "Unexpected field '{$fieldName}' in audit log.");
            
            $this->assertSame((string)$expectedChanges[$fieldName]['old'], $log->getOldValue(), "Old value mismatch for field '{$fieldName}'.");
            $this->assertSame((string)$expectedChanges[$fieldName]['new'], $log->getNewValue(), "New value mismatch for field '{$fieldName}'.");
            $this->assertSame($userId, $log->getUser()->getId());
            unset($expectedChanges[$fieldName]); 
        }
        $this->assertEmpty($expectedChanges, 'Not all expected fields were found in audit logs.');
    }
} 