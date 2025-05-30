<?php

declare(strict_types=1);

namespace App\Service;

interface NotAllowedEmailDomainsServiceInterface
{
    public function check(string $email): bool;
}