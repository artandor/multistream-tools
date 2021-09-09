<?php

namespace App\Provider;

use App\Entity\Account;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class BrimeProvider implements PlatformProviderInterface
{
    public function updateStreamTitleAndCategory(Account $account, string $title, string $category): bool
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
                if ($response->getStatusCode() >= 300) {
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

                    if ($response->getStatusCode() >= 300) {
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
}