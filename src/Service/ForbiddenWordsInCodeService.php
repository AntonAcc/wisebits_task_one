<?php

namespace App\Service;

class ForbiddenWordsInCodeService implements ForbiddenWordsServiceInterface
{
    public function check(string $word): bool
    {
        return false;
    }
}