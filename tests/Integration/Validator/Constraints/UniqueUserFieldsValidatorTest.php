<?php

declare(strict_types=1);

namespace App\Tests\Integration\Validator\Constraints;

use App\Dto\Input\UserCreateDto;
use App\Entity\User;
use App\Validator\Constraints\UniqueUserFields;
use App\Validator\Constraints\UniqueUserFieldsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UniqueUserFieldsValidatorTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private UniqueUserFieldsValidator $validator;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $this->truncateUsersTable();

        $this->validator = new UniqueUserFieldsValidator($this->entityManager);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    private function truncateUsersTable(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $sql = $platform->getTruncateTableSQL('users', true);
        $connection->executeStatement($sql);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testValidateWithNoExistingUser(): void
    {
        $dto = new UserCreateDto();
        $dto->name = 'newuser';
        $dto->email = 'new@example.com';
        
        $constraint = new UniqueUserFields();

        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($dto, $constraint);
    }

    public function testValidateWithExistingUserName(): void
    {
        // Arrange: Create a user that already exists in the database.
        $existingUser = new User('existinguser', 'existing@example.com');
        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();
        
        // Act: Create a DTO with the same name.
        $dto = new UserCreateDto();
        $dto->name = 'existinguser';
        $dto->email = 'new@example.com';

        $constraint = new UniqueUserFields();
        $constraint->nameMessage = 'This name is already used.';

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->with('name')->willReturnSelf();
        $violationBuilder->method('setParameter')->willReturnSelf(); // Mock other chained calls if any
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->nameMessage)
            ->willReturn($violationBuilder);
        
        // Assert
        $this->validator->validate($dto, $constraint);
    }

    public function testValidateWithExistingUserEmail(): void
    {
        $existingUser = new User('anotheruser', 'existing@example.com');
        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();

        $dto = new UserCreateDto();
        $dto->name = 'newuser';
        $dto->email = 'existing@example.com';
        
        $constraint = new UniqueUserFields();
        $constraint->emailMessage = 'This email is already used.';

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('atPath')->with('email')->willReturnSelf();
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');
        
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->emailMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($dto, $constraint);
    }

    public function testValidateWithExistingSoftDeletedUserDoesNotConflict(): void
    {
        $deletedUser = new User('deleteduser', 'deleted@example.com');
        $deletedUser->setDeleted();
        $this->entityManager->persist($deletedUser);
        $this->entityManager->flush();

        $dto = new UserCreateDto();
        $dto->name = 'deleteduser';
        $dto->email = 'new-different@example.com';
        
        $constraint = new UniqueUserFields();

        $this->context->expects($this->never())->method('buildViolation');
        
        $this->validator->validate($dto, $constraint);
    }

    public function testValidateThrowsExceptionForInvalidValueType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(new \stdClass(), new UniqueUserFields());
    }
} 