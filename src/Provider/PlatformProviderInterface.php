<?php

namespace App\Provider;

use App\Entity\Account;

interface PlatformProviderInterface
{
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category): bool;
}