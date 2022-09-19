<?php

namespace App\Provider;

use App\Entity\Account;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BrimeProvider extends AbstractPlatformProvider
{
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category, int $retry = 1): bool
    {
        $client = HttpClient::create();
        if (strlen($category) > 0) {
            try {
                $response = $client->request(
                    'GET',
                    'https://api.brime.tv/v1/categories/search/'.rawurlencode(strtolower($category)), [
                        'headers' => [
                            'Authorization' => 'Bearer '.$account->getAccessToken(),
                            'Content-Type' => 'application/json',
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

                $categories = $response->toArray();

                $selectedCategory = null;

                $categoryId = 0;

                foreach ($categories as $brimeCategory) {
                    if (strtolower($brimeCategory['name']) === strtolower($category)) {
                        $selectedCategory = $brimeCategory;
                    }
                }

                if ($selectedCategory) {
                    $categoryId = $selectedCategory['xid'] ?? 0;
                }

                try {
                    $response = $client->request(
                        'POST',
                        'https://api.brime.tv/v1/channel_settings/stream', [
                            'headers' => [
                                'Authorization' => 'Bearer '.$account->getAccessToken(),
                                'Content-Type' => 'application/json',
                            ],
                            'json' => [
                                'title' => $title,
                                'category_xid' => $categoryId,
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
            $response = $client->request('POST', 'https://auth.brime.tv/oauth/token', [
                'body' => [
                    'client_id' => $_ENV['OAUTH_BRIME_CLIENT_ID'],
                    'client_secret' => $_ENV['OAUTH_BRIME_CLIENT_SECRET'],
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
            // Retrieve the username to use it in the channel request
            $response = $client->request(
                'GET',
                'https://api.brime.tv/v1/account/me', [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            if (true === $this->shouldRetryRequest($response, $account)) {
                // If the token was refreshed, retry the whole function.
                return $this->getFollowerCount($account, --$retry);
            }

            if (false === $this->shouldRetryRequest($response, $account)) {
                return null;
            }
            $responseData = $response->toArray();

            $response = $client->request(
                'GET',
                'https://api.brime.tv/v1/channels/slug/'.$responseData['username']
            );

            if (true === $this->shouldRetryRequest($response, $account)) {
                // If the token was refreshed, retry the whole function.
                return $this->getFollowerCount($account, --$retry);
            }

            if (false === $this->shouldRetryRequest($response, $account)) {
                return null;
            }

            $followerCount = $response->toArray()['followers'];
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface $e) {
            $this->logger->error('An error occured : ' . $e->getMessage());

            return null;
        }

        return $followerCount;
    }
}
