<?php

declare(strict_types=1);

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Validator\Constraints\UniqueUserFields;
use App\Validator\Constraints\UniqueUserFieldsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class UniqueUserFieldsValidatorTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UniqueUserFieldsValidator $validator;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        
        // Очищаем таблицу пользователей перед каждым тестом, чтобы избежать конфликтов
        $this->truncateUsers();

        // Валидатор должен быть создан через фабрику или напрямую с зависимостями
        // Поскольку мы тестируем сам валидатор, создадим его напрямую
        $this->validator = new UniqueUserFieldsValidator($this->entityManager);
        
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    private function truncateUsers(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $connection->executeStatement($platform->getTruncateTableSQL('users', true /* CASCADE */));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks
        $this->validator = null;
    }

    public function testValidateWithNoExistingUser(): void
    {
        $user = new User('newuser', 'new@example.com');
        $constraint = new UniqueUserFields();

        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($user, $constraint);
    }

    public function testValidateWithExistingUserName(): void
    {
        $existingUser = new User('existinguser', 'existing@example.com');
        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();

        $newUser = new User('existinguser', 'new@example.com');
        $constraint = new UniqueUserFields();
        $constraint->nameMessage = 'This name is already used.';

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('atPath')->with('name')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->nameMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($newUser, $constraint);
    }

    public function testValidateWithExistingUserEmail(): void
    {
        $existingUser = new User('anotheruser', 'existing@example.com');
        $this->entityManager->persist($existingUser);
        $this->entityManager->flush();

        $newUser = new User('newuser', 'existing@example.com');
        $constraint = new UniqueUserFields();
        $constraint->emailMessage = 'This email is already used.';

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->expects($this->once())->method('atPath')->with('email')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->emailMessage)
            ->willReturn($violationBuilder);

        $this->validator->validate($newUser, $constraint);
    }
    
    public function testValidateWithExistingSoftDeletedUserNameDoesNotConflict(): void
    {
        $deletedUser = new User('deleteduser', 'deleted@example.com');
        $deletedUser->setDeleted(); // Мягкое удаление
        $this->entityManager->persist($deletedUser);
        $this->entityManager->flush();

        $newUser = new User('deleteduser', 'new@example.com');
        $constraint = new UniqueUserFields();

        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($newUser, $constraint);
    }

    public function testValidateWithExistingSoftDeletedUserEmailDoesNotConflict(): void
    {
        $deletedUser = new User('anotherdeleted', 'deleted.email@example.com');
        $deletedUser->setDeleted(); // Мягкое удаление
        $this->entityManager->persist($deletedUser);
        $this->entityManager->flush();

        $newUser = new User('newuser', 'deleted.email@example.com');
        $constraint = new UniqueUserFields();

        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($newUser, $constraint);
    }

    public function testValidateIgnoresSameUserOnUpdate(): void
    {
        $user = new User('originaluser', 'original@example.com');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Загружаем пользователя из БД, чтобы у него был ID
        $userFromDb = $this->entityManager->find(User::class, $user->getId());
        $this->assertNotNull($userFromDb);

        $constraint = new UniqueUserFields();

        // Имитируем ситуацию, когда валидатор проверяет уже существующего пользователя (например, при PATCH)
        // В этом случае он не должен выдавать ошибку на свои же name и email
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($userFromDb, $constraint);
    }
    
    public function testValidateThrowsExceptionForInvalidValueType(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(new \stdClass(), new UniqueUserFields());
    }
} 