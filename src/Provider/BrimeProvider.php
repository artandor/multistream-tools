<?php

namespace App\Provider;

use App\Entity\Account;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BrimeProvider extends AbstractPlatformProvider
{
    public function __construct(private EntityManagerInterface $entityManager, private ClientRegistry $clientRegistry)
    {
    }

    public function updateStreamTitleAndCategory(Account $account, string $title, string $category, int $retry = 1): bool
    {
        $client = HttpClient::create();
        if (strlen($category) > 0) {
            try {
                $response = $client->request(
                    'GET',
                    'https://api.brime.tv/v1/categories/search/' . rawurlencode(strtolower($category)), [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $account->getAccessToken(),
                            'Content-Type' => 'application/json',
                            'Client-Id' => $_ENV['OAUTH_TWITCH_CLIENT_ID']
                        ]
                    ]
                );

                if ($response->getStatusCode() == 401) {
                    $response->cancel();
                    $account = $this->refreshToken($account);
                    if (!$account) {
                        return false;
                    }
                    // If the token was refreshed, retry the whole function.
                    return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
                } else if ($response->getStatusCode() >= 300) {
                    return false;
                }

                $responseData = $response->toArray();
                if (isset($responseData[0])) {
                    $categoryId = $responseData[0]['xid'];
                }

                try {
                    $response = $client->request(
                        'POST',
                        'https://api.brime.tv/v1/channels/stream', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $account->getAccessToken(),
                                'Content-Type' => 'application/json',
                            ],
                            'json' => [
                                'title' => $title,
                                'category' => $categoryId ?? 0,
                            ]
                        ]
                    );

                    if ($response->getStatusCode() == 401) {
                        $response->cancel();
                        $account = $this->refreshToken($account);
                        if (!$account) {
                            return false;
                        }
                        // If the token was refreshed, retry the whole function.
                        return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
                    } else if ($response->getStatusCode() >= 300) {
                        return false;
                    }
                } catch (TransportExceptionInterface | ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $e) {
                    dump($e);
                }
            } catch (TransportExceptionInterface | ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $e) {
                dd($e);
            }
        }


        return true;
    }

    public function refreshToken(Account $account): ?Account
    {
        $client = HttpClient::create();
        try {
            $response = $client->request('POST', 'https://auth.brime.tv/oauth/token', [
                'body' => [
                    'client_id' => $_ENV['OAUTH_BRIME_CLIENT_ID'],
                    'client_secret' => $_ENV['OAUTH_BRIME_CLIENT_SECRET'],
                    'refresh_token' => $account->getRefreshToken(),
                    'grant_type' => 'refresh_token'
                ]
            ]);
            if ($response->getStatusCode() >= 300) {
                return null;
            }
        } catch (TransportExceptionInterface $e) {
            return null;
        }
        try {
            $account->setAccessToken(json_decode($response->getContent())->access_token);
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            return null;
        }
        $this->entityManager->flush();
        dump('Refreshed token for ' . $account->getPlatform()->getName());
        return $account;
    }
}