<?php

declare(strict_types=1);

namespace App\Service;

readonly class NotAllowedEmailDomainsInCodeService implements NotAllowedEmailDomainsServiceInterface
{
    public function __construct(
        private array $notAllowedEmailDomains = ['notAllowedEmailDomain.com']
    ) {}

    public function check(string $email): bool
    {
        $domain = substr(strrchr($email, "@"), 1);

        return in_array(strtolower($domain), array_map('strtolower', $this->notAllowedEmailDomains), true);
    }

}