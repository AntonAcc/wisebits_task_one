<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\User;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class CreateUserResourceTest extends TestUserResourceAbstract
{
    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'testusercreate',
            'email' => 'test.user.create@example.com',
            'notes' => 'Initial notes for creation test.',
        ];

        $response = $this->apiTestClient->request('POST', '/api/users', [
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

        $auditLogs = $this->userAuditLogRepository->findBy(['user' => $dbUser]);

        $this->assertCount(4, $auditLogs);

        $loggedFields = [];
        foreach ($auditLogs as $log) {
            $loggedFields[] = $log->getFieldName();
            $this->assertNull($log->getOldValue());
            $this->assertNotNull($log->getNewValue());
            $this->assertSame($dbUser->getId(), $log->getUser()->getId());

            $this->assertSame(
                match($log->getFieldName()) {
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'notes' => $userData['notes'],
                    'created' => $dbUser->getCreated()->format('Y-m-d H:i:s P'),
                },
                $log->getNewValue()
            );
        }

        $expectedAuditFields = ['name', 'email', 'created', 'notes'];
        foreach ($expectedAuditFields as $expectedField) {
            $this->assertContains($expectedField, $loggedFields, "Audit log for field '{$expectedField}' is missing.");
        }
    }
} 