<?php

declare(strict_types=1);

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\NotAllowedEmailDomainsInCodeService;
use App\Validator\Constraints\AllowedEmailDomains;
use App\Validator\Constraints\AllowedEmailDomainsValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class AllowedEmailDomainsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): AllowedEmailDomainsValidator
    {
        return new AllowedEmailDomainsValidator(new NotAllowedEmailDomainsInCodeService(['notallowed.com']));
    }

    public function testAllowed(): void
    {
        $this->validator->validate('test@allowed.com', new AllowedEmailDomains());
        $this->assertNoViolation();
    }

    public function testNotAllowed(): void
    {
        $this->validator->validate('test@notallowed.com', new AllowedEmailDomains());
        $this
            ->buildViolation('This email domain is not allowed.')
            ->atPath('property.path.email')
            ->assertRaised();
    }
}