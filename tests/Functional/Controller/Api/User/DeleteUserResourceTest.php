<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use App\Entity\User;
use App\Entity\UserAuditLog;
use Symfony\Component\HttpFoundation\Response;

final class DeleteUserResourceTest extends TestUserResourceAbstract
{
    public function testDeleteUser(): void
    {
        $client = static::createClient();

        $name = 'deletetestuser';
        $email = 'delete.test.user@example.com';
        $notes = 'Some notes for delete test.';
        
        $user = new User($name, $email, $notes);
        $this->userRepository->save($user);
        $userId = $user->getId();

        $this->truncateEntities([UserAuditLog::class]);

        $client->request('DELETE', '/api/users/' . $userId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->refreshEntityManager();

        $this->disableFilters();
        /** @var User|null $deletedUser */
        $deletedUser = $this->userRepository->find($userId);
        $this->enableFilters();
        $this->assertNotNull($deletedUser, 'User should still exist in DB after soft delete.');
        $this->assertNotNull($deletedUser->getDeleted(), 'Deleted date should be set.');
        $deletedDate = $deletedUser->getDeleted();

        $auditLogs = $this->userAuditLogRepository->findBy(['user' => $deletedUser], ['changedAt' => 'ASC']);
        $this->assertCount(1, $auditLogs, 'Expected 1 audit log for delete operation.');

        $deleteLog = $auditLogs[0];
        $this->assertSame('deleted', $deleteLog->getFieldName());
        $this->assertNull($deleteLog->getOldValue());
        $this->assertNotNull($deleteLog->getNewValue());
        $this->assertSame($deletedDate->format('Y-m-d H:i:s P'), $deleteLog->getNewValue());
    }

    public function testDeleteNonExistentUser(): void
    {
        $client = static::createClient();
        $nonExistentUserId = 9999999;

        $client->request('DELETE', '/api/users/' . $nonExistentUserId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $auditLogs = $this->userAuditLogRepository->findAll();
        $this->assertCount(0, $auditLogs, 'No audit logs should be created for deleting a non-existent user.');
    }

    public function testDeleteAlreadyDeletedUser(): void
    {
        $client = static::createClient();

        $name = 'alreadydeleteduser';
        $email = 'already.deleted.user@example.com';
        
        $user = new User($name, $email);
        $user->setDeleted();

        $reflectionClass = new \ReflectionClass(User::class);
        $createdProperty = $reflectionClass->getProperty('created');
        $deletedProperty = $reflectionClass->getProperty('deleted');

        $createdDate = new \DateTimeImmutable('-1 hour');
        $createdProperty->setValue($user, $createdDate);
        $deletedProperty->setValue($user, $createdDate->modify('+1 minute'));

        $firstDeletedDate = $user->getDeleted();
        $this->userRepository->save($user);
        $userId = $user->getId();

        $this->disableFilters();
        $softDeletedUser = $this->userRepository->find($userId);
        $this->assertNotNull($softDeletedUser);
        $this->enableFilters();

        $this->truncateEntities([\App\Entity\UserAuditLog::class]);

        $client->request('DELETE', '/api/users/' . $userId);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND, 'DELETE request to already deleted user should return 404.');


        $this->refreshEntityManager();

        $this->disableFilters();
        $stillSoftDeletedUser = $this->userRepository->find($userId);
        $this->enableFilters();
        $this->assertNotNull($stillSoftDeletedUser, 'User should still exist (soft-deleted) in DB after 404 on second delete attempt.');
        $this->assertNotNull($stillSoftDeletedUser->getDeleted(), 'Deleted date should still be set.');
        $this->assertSame(
            $firstDeletedDate->getTimestamp(), 
            $stillSoftDeletedUser->getDeleted()->getTimestamp(), 
            'Deleted date should not change after a 404 on second delete attempt.'
        );

        $auditLogs = $this->userAuditLogRepository->findAll();
        $this->assertCount(0, $auditLogs, 'No new audit logs should be created when attempting to delete an already deleted user (resulting in 404).');
    }
} 