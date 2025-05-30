<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator\Constraints;

use App\Service\ForbiddenWordsInCodeService;
use App\Validator\Constraints\NotForbiddenWords;
use App\Validator\Constraints\NotForbiddenWordsValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class NotForbiddenWordsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotForbiddenWordsValidator
    {
        return new NotForbiddenWordsValidator(new ForbiddenWordsInCodeService(['forbidden']));
    }

    public function testValidWord(): void
    {
        $this->validator->validate('someword', new NotForbiddenWords());
        $this->assertNoViolation();
    }

    public function testInvalidWordSame(): void
    {
        $this->validator->validate('forbidden', new NotForbiddenWords());
        $this
            ->buildViolation('This username contains some forbidden words')
            ->atPath('property.path.name')
            ->assertRaised();
    }

    public function testInvalidWordPrefix(): void
    {
        $this->validator->validate('forbiddensomethingelse', new NotForbiddenWords());
        $this
            ->buildViolation('This username contains some forbidden words')
            ->atPath('property.path.name')
            ->assertRaised();
    }

    public function testInvalidWordPostfix(): void
    {
        $this->validator->validate('somethingforbidden', new NotForbiddenWords());
        $this
            ->buildViolation('This username contains some forbidden words')
            ->atPath('property.path.name')
            ->assertRaised();
    }

    public function testInvalidWordMiddle(): void
    {
        $this->validator->validate('somethingforbiddensomething', new NotForbiddenWords());
        $this
            ->buildViolation('This username contains some forbidden words')
            ->atPath('property.path.name')
            ->assertRaised();
    }
}