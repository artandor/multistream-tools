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
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category): bool
    {
        $client = HttpClient::create();
        try {
            $response = $client->request(
                'GET',
                'https://youtube.googleapis.com/youtube/v3/liveBroadcasts?part=snippet&broadcastStatus=active&maxResults=1&key=' . $_ENV['OAUTH_GOOGLE_API_SECRET'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                ]
            );
            if ($response->getStatusCode() >= 300) {
                return false;
            }

            $responseData = $response->toArray();
            if (count($responseData['items']) <= 0) {
                dump('You are not currently streaming to youtube. Title update did not happen.');
                return false;
            }
            $streamId = $responseData['items'][0]['id'];


            $response = $client->request(
                'GET',
                'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $streamId . '&key=' . $_ENV['OAUTH_GOOGLE_API_SECRET'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                ]
            );
            if ($response->getStatusCode() >= 300) {
                return false;
            }

            $responseData = $response->toArray();

            $categoryId = $responseData['items'][0]['snippet']['categoryId'];
            // @TODO : we should find the category if the user set one but I (Artandor) couldn't find any API route to search a category
            dump($responseData, $categoryId);

            $response = $client->request(
                'PUT',
                'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $streamId . '&key=' . $_ENV['OAUTH_GOOGLE_API_SECRET'], [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $account->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'json' => [
                        'id' => $streamId,
                        'snippet' => [
                            'title' => $title,
                            'categoryId' => $categoryId
                        ]
                    ]
                ]
            );

            if ($response->getStatusCode() >= 300) {
                return false;
            }
        } catch (TransportExceptionInterface | ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $e) {
            dump('An error occured');
            return false;
        }
        return true;
    }

    public function refreshToken(Account $account): ?Account
    {
        // TODO: Implement refreshToken() method.
        return null;
    }
}