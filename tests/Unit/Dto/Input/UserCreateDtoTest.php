<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Input;

use App\Dto\Input\UserCreateDto;
use App\Tests\Unit\Entity\UserTest\StubValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Tests\Unit\Entity\UserTest\MockConstraintValidatorFactory;

final class UserCreateDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(new MockConstraintValidatorFactory())
            ->getValidator();
    }

    #[DataProvider('provideValidUserData')]
    public function testValidDto(string $name, string $email, ?string $notes): void
    {
        $dto = new UserCreateDto();
        $dto->name = $name;
        $dto->email = $email;
        $dto->notes = $notes;

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations, (string) $violations);
    }

    public static function provideValidUserData(): iterable
    {
        yield ['validname', 'valid@example.com', null];
        yield ['anotheruser01', 'another.user@example.co.uk', 'Some notes'];
        // Убрал слишком длинный кейс для notes, т.к. в DTO нет валидации длины для notes
        yield ['longusername123', 'long.user.name.123@example-domain.com', 'Some notes about user.'];
    }

    #[DataProvider('provideInvalidUserData')]
    public function testInvalidDto(string $name, string $email, ?string $notes, int $expectedViolationsCount, array $expectedViolationMessages = []): void
    {
        $dto = new UserCreateDto();
        $dto->name = $name;
        $dto->email = $email;
        $dto->notes = $notes;

        $violations = $this->validator->validate($dto);

        $this->assertCount($expectedViolationsCount, $violations, (string) $violations);

        foreach ($expectedViolationMessages as $path => $messageFragment) {
            $found = false;
            foreach ($violations as $violation) {
                if ($violation->getPropertyPath() === $path && str_contains((string)$violation->getMessage(), $messageFragment)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, sprintf("Violation for path '%s' with message containing '%s' not found. Violations: %s", $path, $messageFragment, (string)$violations));
        }
    }

    public static function provideInvalidUserData(): iterable
    {
        yield 'name_is_short' => ['short', 'valid@example.com', null, 1, ['name' => 'This value is too short.']];
        yield 'name_has_invalid_char' => ['invalid-char', 'valid@example.com', null, 1, ['name' => 'Name can only contain lowercase letters and numbers.']];
        yield 'name_is_too_long' => [str_repeat('a', 65), 'valid@example.com', null, 1, ['name' => 'This value is too long.']];

        yield 'email_is_invalid' => ['validname', 'invalid-email', null, 1, ['email' => 'This value is not a valid email address.']];
        yield 'email_is_too_long' => ['validname', str_repeat('a', 247) . '@example.com', null, 1, ['email' => 'This value is too long.']];

        yield 'name_empty_and_email_empty' => [
            '',
            '',
            null,
            3,
            [
                'name' => 'should not be blank',
                'email' => 'should not be blank',
            ]
        ];

        yield 'name_empty_and_email_valid' => [
            '',
            'valid@example.com',
            null,
            2,
            [
                'name' => 'should not be blank',
            ]
        ];
        
        yield 'name_valid_and_email_empty' => ['validname', '', null, 1, ['email' => 'should not be blank']];
    }
} 