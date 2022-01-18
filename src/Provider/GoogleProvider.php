<?php

namespace App\Provider;

use App\Entity\Account;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GoogleProvider extends AbstractPlatformProvider
{
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category, int $retry = 1): bool
    {
        $client = HttpClient::create();
        try {
            $response = $client->request(
                'GET',
                'https://youtube.googleapis.com/youtube/v3/liveBroadcasts?part=snippet&broadcastStatus=active&maxResults=1&key='.$_ENV['OAUTH_GOOGLE_API_SECRET'], [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if (true === $this->shouldRetryRequest($response, $account)) {
                $this->logger->warning('Retrying for platform youtube after failed broadcast lookup.');
                // If the token was refreshed, retry the whole function.
                return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
            }

            if (false === $this->shouldRetryRequest($response, $account)) {
                return false;
            }

            $responseData = $response->toArray();
            if (count($responseData['items']) <= 0) {
                $this->logger->warning('You are not currently streaming to youtube. Title update did not happen.');

                return false;
            }
            $streamId = $responseData['items'][0]['id'];

            $response = $client->request(
                'GET',
                'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$streamId.'&key='.$_ENV['OAUTH_GOOGLE_API_SECRET'], [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if (true === $this->shouldRetryRequest($response, $account)) {
                $this->logger->warning('Retrying for platform youtube after failed stream Id lookup.');
                // If the token was refreshed, retry the whole function.
                return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
            }

            if (false === $this->shouldRetryRequest($response, $account)) {
                return false;
            }

            $responseData = $response->toArray();

            $categoryId = $responseData['items'][0]['snippet']['categoryId'];
            // @TODO : we should find the category if the user set one but I (Artandor) couldn't find any API route to search a category
            // To do so, we need to study the youtube studio dashboard behaviour to do the same requests because it's not documented ...

            $response = $client->request(
                'PUT',
                'https://www.googleapis.com/youtube/v3/videos?part=snippet&id='.$streamId.'&key='.$_ENV['OAUTH_GOOGLE_API_SECRET'], [
                    'headers' => [
                        'Authorization' => 'Bearer '.$account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'id' => $streamId,
                        'snippet' => [
                            'title' => $title,
                            'categoryId' => $categoryId,
                        ],
                    ],
                ]
            );

            if (true === $this->shouldRetryRequest($response, $account)) {
                $this->logger->warning('Retrying for platform youtube after failed update tile.');
                // If the token was refreshed, retry the whole function.
                return $this->updateStreamTitleAndCategory($account, $title, $category, --$retry);
            }

            if (false === $this->shouldRetryRequest($response, $account)) {
                return false;
            }
        } catch (TransportExceptionInterface|ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error('An error occured : '.$e->getMessage());

            return false;
        }

        return true;
    }

    public function refreshToken(Account $account): ?Account
    {
        $client = HttpClient::create();
        try {
            $response = $client->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'client_id' => $_ENV['OAUTH_GOOGLE_CLIENT_ID'],
                    'client_secret' => $_ENV['OAUTH_GOOGLE_CLIENT_SECRET'],
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
}
