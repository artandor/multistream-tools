<?php

namespace App\Provider;

use App\Entity\Account;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TrovoProvider extends AbstractPlatformProvider
{
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category, int $retry = 1): bool
    {
        $client = HttpClient::create();
        if (strlen($category) > 0) {
            try {
                $response = $client->request(
                    'POST',
                    'https://open-api.trovo.live/openplatform/searchcategory', [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Client-Id' => $_ENV['OAUTH_TROVO_CLIENT_ID'],
                        ],
                        'json' => [
                            'query' => $category,
                            'limit' => 1,
                        ],
                    ]
                );

                if (true === $this->shouldRetryRequest($response, $account)) {
                    // If the token was refreshed, retry the whole function.
                    return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
                }

                if (false === $this->shouldRetryRequest($response, $account)) {
                    return false;
                }

                $responseData = $response->toArray();
                if (isset($responseData['category_info'])) {
                    $categoryId = $responseData['category_info'][0]['id'];
                }

                try {
                    $response = $client->request(
                        'POST',
                        'https://open-api.trovo.live/openplatform/channels/update', [
                            'headers' => [
                                'Authorization' => 'OAuth '.$account->getAccessToken(),
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                                'Client-Id' => $_ENV['OAUTH_TROVO_CLIENT_ID'],
                            ],
                            'json' => [
                                'channel_id' => $account->getExternalId(),
                                'live_title' => $title,
                                'category_id' => $categoryId ?? null,
                            ],
                        ]
                    );

                    if (true === $this->shouldRetryRequest($response, $account)) {
                        // If the token was refreshed, retry the whole function.
                        return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
                    }

                    if (false === $this->shouldRetryRequest($response, $account)) {
                        $this->logger->error('Could\'nt refresh token. The user have to login again');

                        return false;
                    }
                } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                    $this->logger->error('An error occured : '.$e->getMessage());

                    return false;
                }
            } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                $this->logger->error('An error occured : '.$e->getMessage());

                return false;
            }
        }

        return true;
    }

    public function refreshToken(Account $account): ?Account
    {
        $client = HttpClient::create();
        try {
            $response = $client->request('POST', 'https://open-api.trovo.live/openplatform/refreshtoken', [
                'headers' => [
                    'Authorization' => '',
                    'Accept' => 'application/json',
                    'Client-Id' => $_ENV['OAUTH_TROVO_CLIENT_ID'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'client_secret' => $_ENV['OAUTH_TROVO_CLIENT_SECRET'],
                    'refresh_token' => $account->getRefreshToken(),
                    'grant_type' => 'refresh_token',
                ],
            ]);
            if ($response->getStatusCode() >= 300) {
                return null;
            }
        } catch (TransportExceptionInterface $e) {
            return null;
        }
        try {
            $account->setAccessToken(json_decode($response->getContent())->access_token);
            $account->setRefreshToken(json_decode($response->getContent())->refresh_token);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
        $this->entityManager->flush();
        $this->logger->info('Refreshed token for '.$account->getPlatform()->getName());

        return $account;
    }

    public function getFollowerCount(Account $account, int $retry = 1): ?int
    {
        $client = HttpClient::create();

        try {
            $response = $client->request(
                'POST',
                'https://open-api.trovo.live/openplatform/channels/id', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Client-Id' => $_ENV['OAUTH_TROVO_CLIENT_ID'],
                    ],
                    'json' => [
                        'channel_id' => $account->getExternalId(),
                    ],
                ]
            );

            if (true === $this->shouldRetryRequest($response, $account)) {
                // If the token was refreshed, retry the whole function.
                return $this->getFollowerCount($account, --$retry);
            }

            if (false === $this->shouldRetryRequest($response, $account)) {
                return false;
            }

            $followerCount = $response->toArray()['followers'];
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }

        return $followerCount;
    }
}
