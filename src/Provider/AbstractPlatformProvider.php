<?php

namespace App\Provider;

use App\Entity\Account;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPlatformProvider
{
    abstract public function updateStreamTitleAndCategory(Account $account, string $title, string $category): bool;

    abstract public function refreshToken(Account $account): ?Account;

    protected function checkResponseAndRefreshToken(ResponseInterface $response, Account $account): ?bool
    {
        if ($response->getStatusCode() == 401) {
            $response->cancel();
            $account = $this->refreshToken($account);
            if (!$account) {
                return false;
            }
            // If the token was refreshed, send true to retry the whole function.
            return true;
        } else if ($response->getStatusCode() >= 300) {
            // If the response is wrong, exit and fail treatment
            return false;
        }
        // Continue treatment, there are no errors
        return null;
    }
}