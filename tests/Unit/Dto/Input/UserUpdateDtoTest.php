<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Input;

use App\Dto\Input\UserUpdateDto;
use App\Tests\Unit\Entity\UserTest\MockConstraintValidatorFactory;
use App\Tests\Unit\Entity\UserTest\StubValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserUpdateDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory(new MockConstraintValidatorFactory())
            ->getValidator();
    }

    #[DataProvider('provideValidUpdateData')]
    public function testValidUpdateDto(array $data): void
    {
        $dto = new UserUpdateDto();
        if (array_key_exists('name', $data)) {
            $dto->name = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $dto->email = $data['email'];
        }
        if (array_key_exists('notes', $data)) {
            $dto->notes = $data['notes'];
        }

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations, (string) $violations);
    }

    public static function provideValidUpdateData(): iterable
    {
        yield 'no_fields_provided' => [[]]; // Нет полей - валидно
        yield 'name_only_valid' => [['name' => 'validname']];
        yield 'email_only_valid' => [['email' => 'valid@example.com']];
        yield 'notes_only_valid' => [['notes' => 'Some notes.']];
        yield 'name_and_email_valid' => [['name' => 'anothername', 'email' => 'another@example.com']];
        yield 'all_fields_valid' => [['name' => 'allvalid', 'email' => 'all@valid.com', 'notes' => 'All notes here.']];
        yield 'name_null_valid' => [['name' => null]];
        yield 'email_null_valid' => [['email' => null]];
    }

    #[DataProvider('provideInvalidUpdateData')]
    public function testInvalidUpdateDto(array $data, int $expectedViolationsCount, array $expectedViolationMessages = []): void
    {
        $dto = new UserUpdateDto();
        if (array_key_exists('name', $data)) {
            $dto->name = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $dto->email = $data['email'];
        }

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

    public static function provideInvalidUpdateData(): iterable
    {
        yield 'name_too_short' => [['name' => 'short'], 1, ['name' => 'This value is too short.']];
        yield 'name_invalid_char' => [['name' => 'invalid-char'], 1, ['name' => 'Name can only contain lowercase letters and numbers.']];
        yield 'name_too_long' => [['name' => str_repeat('a', 65)], 1, ['name' => 'This value is too long.']];
        yield 'name_empty_string_violates_length' => [['name' => ''], 1, ['name' => 'This value is too short.']];

        yield 'email_invalid' => [['email' => 'invalid-email'], 1, ['email' => 'This value is not a valid email address.']];
        yield 'email_too_long' => [['email' => str_repeat('a', 247) . '@example.com'], 1, ['email' => 'This value is too long.']];
        yield 'email_empty_string_violates_not_blank' => [['email' => ''], 1, ['email' => 'This value should not be blank.']];

        yield 'name_short_email_invalid' => [['name' => 'shrt', 'email' => 'invalid'], 2];
    }
} 