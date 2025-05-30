<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\UserAuditLog;

final class GetUserResourceTest extends TestUserResourceAbstract
{
    public function testGetUser(): void
    {
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
        $this->truncateEntities([UserAuditLog::class]);

        $response = $this->apiTestClient->request('GET', '/api/users/' . $userId);

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

        $response = $this->apiTestClient->request('GET', '/api/users/' . $userId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
//        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

//        $responseData = $response->toArray();
//
//        $this->assertSame($userId, $responseData['id']);
//        $this->assertSame($name, $responseData['name']);
//        $this->assertSame($email, $responseData['email']);
//        $this->assertSame($notes, $responseData['notes']);
//        $this->assertNotNull($responseData['created']);
//        $this->assertSame($createdDate->format('Y-m-d H:i:s P'), (new \DateTimeImmutable($responseData['created']))->format('Y-m-d H:i:s P'));
//        $this->assertArrayHasKey('deleted', $responseData, "The 'deleted' key should exist in the response.");
//        $this->assertNull($responseData['deleted'], "The 'deleted' field should be null for a new user.");
//
//        // Verify no audit logs were created for a GET request
//        $auditLogs = $this->userAuditLogRepository->findBy(['user' => $userId]);
//        $this->assertCount(0, $auditLogs, 'No audit logs should be created for a GET request.');
    }
}