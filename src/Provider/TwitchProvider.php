<?php

namespace App\Provider;

use App\Entity\Account;
use App\Exception\CategoryNotFound;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TwitchProvider extends AbstractPlatformProvider
{
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category, int $retry = 1): bool
    {
        if ($retry < 0) {
            return false;
        }
        $client = HttpClient::create();
        if ('' !== $category) {
            try {
                $response = $client->request(
                    'GET',
                    'https://api.twitch.tv/helix/search/categories?query='.$category.'&first=1', [
                        'headers' => [
                            'Authorization' => 'Bearer '.$account->getAccessToken(),
                            'Content-Type' => 'application/json',
                            'Client-Id' => $_ENV['OAUTH_TWITCH_CLIENT_ID'],
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
                if (empty($responseData['data'])) {
                    throw new CategoryNotFound();
                }
                $category = $responseData['data'][0]['id'];
            } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
                $this->logger->error('An error occured : '.$e->getMessage());
            } catch (CategoryNotFound) {
                return false;
            }
        }

        try {
            $response = $client->request(
                'PATCH',
                'https://api.twitch.tv/helix/channels?broadcaster_id='.$account->getExternalId(), [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Client-Id' => $_ENV['OAUTH_TWITCH_CLIENT_ID'],
                    ],
                    'json' => [
                        'game_id' => $category,
                        'title' => $title,
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

        return true;
    }

    public function refreshToken(Account $account): ?Account
    {
        $client = HttpClient::create();
        try {
            $response = $client->request('POST', 'https://id.twitch.tv/oauth2/token?grant_type=refresh_token&refresh_token='.
                $account->getRefreshToken().'&client_id='.$_ENV['OAUTH_TWITCH_CLIENT_ID'].'&client_secret='.$_ENV['OAUTH_TWITCH_CLIENT_SECRET']);
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
            $response = $client->request(
                'GET',
                'https://api.twitch.tv/helix/users/follows?to_id='.$account->getExternalId().'&first=1', [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Client-Id' => $_ENV['OAUTH_TWITCH_CLIENT_ID'],
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

            $followerCount = json_decode($response->getContent(), true)['total'];
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error('An error occured : '.$e->getMessage());

            return null;
        }

        return $followerCount;
    }

    public function getSubscriberCount(Account $account, int $retry = 1): ?int
    {
        $client = HttpClient::create();

        try {
            $response = $client->request(
                'GET',
                'https://api.twitch.tv/helix/subscriptions?broadcaster_id='.$account->getExternalId().'&first=1', [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Client-Id' => $_ENV['OAUTH_TWITCH_CLIENT_ID'],
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

            $subscriberCount = json_decode($response->getContent(), true)['total'];
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error('An error occured : '.$e->getMessage());

            return null;
        }

        return $subscriberCount;
    }
}
