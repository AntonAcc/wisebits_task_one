<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Validator\Validator\ValidatorInterface as ValidatorInterface;

final class UserTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

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

    #[DataProvider('provideValidUserData')]
    public function testValidUser(string $name, string $email, ?string $notes): void
    {
        $user = new User($name, $email, $notes);
        $violations = $this->validator->validate($user);
        $this->assertCount(0, $violations);
    }

    public static function provideValidUserData(): iterable
    {
        yield ['validname', 'valid@example.com', null];
        yield ['anotheruser01', 'another.user@example.co.uk', 'Some notes'];
        yield ['longusername123', 'long.user.name.123@example-domain.com', 'Very long notes about this user to test the text field capacity, although it is not strictly validated here.'];
    }

    #[DataProvider('provideInvalidUserData')]
    public function testInvalidUser(string $name, string $email, ?string $notes, int $expectedViolationsCount, array $expectedViolationMessages = []): void
    {
        $user = new User($name, $email, $notes);
        $violations = $this->validator->validate($user);

        $this->assertCount($expectedViolationsCount, $violations);

        foreach ($expectedViolationMessages as $path => $message) {
            $found = false;
            foreach ($violations as $violation) {
                if ($violation->getPropertyPath() === $path && str_contains($violation->getMessage(), $message)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, sprintf("Violation for path '%s' with message containing '%s' not found.", $path, $message));
        }
    }

    public static function provideInvalidUserData(): iterable
    {
        // Invalid Name
        yield ['short', 'valid@example.com', null, 1, ['name' => 'This value is too short. It should have 8 characters or more.']];
        yield ['invalid-char', 'valid@example.com', null, 1, ['name' => 'This value is not valid.']]; // Regex
        yield ['toolongusernamepaddingtotestthesixtyfourcharacterlimitimposedbythedatabaseschemadefinition', 'valid@example.com', null, 1, ['name' => 'This value is too long. It should have 64 characters or less.']]; // Length for DB, though not directly from Assert\Length

        // Invalid Email
        yield ['validname', 'invalid-email', null, 1, ['email' => 'This value is not a valid email address.']];
        yield ['validname', 'emailltoolongpaddingtotestthetwohundredfiftysixcharacterlimitimposedbythedatabaseschemadefinitionemailltoolongpaddingtotestthetwohundredfiftysixcharacterlimitimposedbythedatabaseschemadefinitionemailltoolongpaddingtotestthetwohundredfiftysixcharacterlimit@example.com', null, 1, ['email' => 'This value is too long. It should have 256 characters or less.']];


        // Multiple violations
        yield ['shrt', 'invalid', null, 2]; // Name too short, invalid email
    }

    public function testDeletedCannotBeBeforeCreated(): void
    {
        $user = new User('testuser', 'test@example.com');

        $reflectionClass = new \ReflectionClass(User::class);
        $deletedProperty = $reflectionClass->getProperty('deleted');

        $createdDate = $user->getCreated();
        $invalidDeletedDate = $createdDate->modify('-1 hour');
        $deletedProperty->setValue($user, $invalidDeletedDate);

        $violations = $this->validator->validate($user);

        $this->assertGreaterThan(0, $violations->count(), 'Expected violations for deleted date being before created date.');

        $found = false;
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'deleted' && $violation->getMessage() === 'Deletion date cannot be before creation date.') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Expected violation message for deleted date was not found.');
    }
}