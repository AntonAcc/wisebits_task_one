<?php

declare(strict_types=1);

namespace App\Service;

readonly class ForbiddenWordsInCodeService implements ForbiddenWordsServiceInterface
{
    public function __construct(
        private array $forbiddenWordList = ['admin', 'root', 'superuser']
    ) {}

    public function check(string $word): bool
    {
        foreach ($this->forbiddenWordList as $forbiddenWord) {
            if (mb_strpos(mb_strtolower($word), mb_strtolower($forbiddenWord)) !== false) {
                return true;
            }
        }

        return false;
    }
}