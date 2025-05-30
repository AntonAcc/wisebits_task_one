<?php

declare(strict_types=1);

namespace App\Service;

interface ForbiddenWordsServiceInterface
{
    public function check(string $word): bool;
}