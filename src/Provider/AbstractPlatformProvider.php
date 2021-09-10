<?php

namespace App\Provider;

use App\Entity\Account;

abstract class AbstractPlatformProvider
{
    abstract public function updateStreamTitleAndCategory(Account $account, string $title, string $category): bool;

    abstract public function refreshToken(Account $account): ?Account;
}