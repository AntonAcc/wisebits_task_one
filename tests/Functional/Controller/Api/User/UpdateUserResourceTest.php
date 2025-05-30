<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use App\Entity\User;
use App\Entity\UserAuditLog;
use Symfony\Component\HttpFoundation\Response;

final class UpdateUserResourceTest extends  TestUserResourceAbstract
{

    public function testUpdateUser(): void
    {
        $initialName = 'originaluser';
        $initialEmail = 'original.user@example.com';
        $initialNotes = 'Initial notes.';
        
        $user = new User($initialName, $initialEmail, $initialNotes);
        $this->userRepository->save($user);
        $userId = $user->getId();

        $this->truncateEntities([UserAuditLog::class]);

        $updatedName = 'updateduser';
        $updatedNotes = 'Updated notes.';
        $updateData = [
            'name' => $updatedName,
            'notes' => $updatedNotes,
        ];

        $response = $this->apiTestClient->request('PATCH', '/api/users/' . $userId, [
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

        /** @var User|null $dbUser */
        $dbUser = $this->getFreshUserRepository()->find($userId);
        $this->assertNotNull($dbUser, 'User not found in DB with fresh EM.');

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

    public function testGetDeletedUser(): void
    {
        $name = 'gettestuser';
        $email = 'get.test.user@example.com';
        $notes = 'Some notes for get test.';

        $user = new User($name, $email, $notes);
        $user->setDeleted();
        $this->userRepository->save($user);
        $userId = $user->getId();
        $this->entityManager->clear();

        // Clear audit logs from creation before making the GET request
        $this->truncateEntities([UserAuditLog::class]);

        $updatedName = 'updateduser';
        $updatedNotes = 'Updated notes.';
        $updateData = [
            'name' => $updatedName,
            'notes' => $updatedNotes,
        ];

        $response = $this->apiTestClient->request('PATCH', '/api/users/' . $userId, [
            'json' => $updateData,
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
} 