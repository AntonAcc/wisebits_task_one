<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $name = 'testuser';
        $email = 'test@example.com';
        $notes = 'Some notes about the user.';
        $user = new User($name, $email, $notes);

        $this->assertNull($user->getId());
        $this->assertSame($name, $user->getName());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($notes, $user->getNotes());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getCreated());
        $this->assertNull($user->getDeleted());
    }

    public function testSetters(): void
    {
        $user = new User('initialname', 'initial@example.com');

        $newName = 'updatedname';
        $newEmail = 'updated@example.com';
        $newNotes = 'Updated notes.';

        $user->setName($newName);
        $user->setEmail($newEmail);
        $user->setNotes($newNotes);
        $user->setDeleted();

        $this->assertSame($newName, $user->getName());
        $this->assertSame($newEmail, $user->getEmail());
        $this->assertSame($newNotes, $user->getNotes());
        $this->assertNotNull($user->getDeleted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getDeleted());
    }

    public function testDeletedCannotBeBeforeCreated(): void
    {
        $user = new User('testuser', 'test@example.com');

        $reflectionClass = new \ReflectionClass(User::class);
        
        $createdDate = $user->getCreated(); 
        $invalidDeletedDate = $createdDate->modify('-1 hour');

        $deletedProperty = $reflectionClass->getProperty('deleted');
        $deletedProperty->setValue($user, $invalidDeletedDate);

        $this->assertTrue(
            $user->getDeleted() !== null && $user->getCreated()->getTimestamp() > $user->getDeleted()->getTimestamp(),
            'Deletion date is expected to be before creation date for this specific test setup.'
        );
    }
}