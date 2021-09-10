<?php

namespace App\Provider;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPlatformProvider
{
    public function __construct(protected EntityManagerInterface $entityManager, protected LoggerInterface $logger)
    {
    }

    abstract public function updateStreamTitleAndCategory(Account $account, string $title, string $category): bool;

    abstract public function refreshToken(Account $account): ?Account;

    /**
     * Check the status of a request and determine if it should be retried or not based on status code
     * If the function returns null, this means that no error was found.
     * @param ResponseInterface $response
     * @param Account $account
     * @return bool|null
     */
    protected function shouldRetryRequest(ResponseInterface $response, Account $account): ?bool
    {
        try {
            if ($response->getStatusCode() < 300) {
                // The response is a success, you can continue your treatment.
                return null;
            }

            if ($response->getStatusCode() === 401) {
                $response->cancel();
                $account = $this->refreshToken($account);
                if (!$account) {
                    return false;
                }
                // The token was refreshed, you should retry the initial request
                return true;
            }

            if ($response->getStatusCode() >= 300) {
                // An error occurred in the treatment, no point in retrying
                return false;
            }
        } catch (TransportExceptionInterface $e) {
            $this->logger->error($e->getMessage());
        }
        return false;
    }
}
