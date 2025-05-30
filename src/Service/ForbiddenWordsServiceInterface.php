<?php

namespace App\Service;

interface ForbiddenWordsServiceInterface
{
    public function check(string $word): bool;
}