<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationAvailabilityFunctionalTest extends WebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url, $responseCode = 200, $userEmail = null)
    {
        $client = self::createClient();

        if ($userEmail) {
            $userRepository = static::getContainer()->get(UserRepository::class);
            $user = $userRepository->findOneByEmail($userEmail);
            $client->loginUser($user);
        }
        $client->request('GET', $url);

        $this->assertResponseStatusCodeSame($responseCode);
    }

    public function urlProvider(): \Generator
    {
        yield ['/'];
        yield ['/update-stream-infos', Response::HTTP_FOUND];
        yield ['/update-stream-infos', Response::HTTP_OK, $this->getNormalUserEmail()];
        yield ['/admin', Response::HTTP_UNAUTHORIZED];
        yield ['/admin', Response::HTTP_FORBIDDEN, $this->getNormalUserEmail()];
        yield ['/admin', Response::HTTP_FOUND, $this->getAdminUserEmail()];
        yield ['/privacy-policy'];
        yield ['/logout', Response::HTTP_FOUND, $this->getNormalUserEmail()];
    }

    private function getNormalUserEmail(): string
    {
        return 'user@example.com';
    }

    private function getAdminUserEmail(): string
    {
        return 'admin@example.com';
    }
}
