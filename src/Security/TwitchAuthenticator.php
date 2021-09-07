<?php

namespace App\Security;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Vertisan\OAuth2\Client\Provider\TwitchHelixResourceOwner;

class TwitchAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordEncoder;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_twitch_check';
    }

    public function authenticate(Request $request): PassportInterface
    {
        $client = $this->clientRegistry->getClient('twitch');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {

                /** @var TwitchHelixResourceOwner $twitchUser */
                $twitchUser = $client->fetchUserFromToken($accessToken);

                $email = $twitchUser->getEmail();

                // 1) have they logged in with Facebook before? Easy!
                /** @var Account $account */
                $account = $this->entityManager->getRepository(Account::class)->findOneBy(['platformId' => $twitchUser->getId()]);

                if ($account) {
                    $account->setPlatformName('App\Provider\TwitchProvider');
                    $account->setAccessToken($accessToken);
                    $account->setRefreshToken($accessToken->getRefreshToken());
                    $this->entityManager->flush();
                    return $account->getLinkedTo();
                } else {
                    $user = new User();
                    $user->setEmail($email);
                    $account = new Account();
                    $account->setEmail($email);
                    $account->setPlatformName('App\Provider\TwitchProvider');
                    $account->setAccessToken($accessToken);
                    $account->setRefreshToken($accessToken->getRefreshToken());
                    $account->setPlatformId($twitchUser->getId());
                    $user->addAccount($account);
                    $user->setPassword($this->passwordEncoder->hashPassword($user, md5(random_bytes(16))));
                }
                $this->entityManager->persist($user);
                $this->entityManager->persist($account);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // change "app_homepage" to some route in your app
        $targetUrl = $this->router->generate('home');

        return new RedirectResponse($targetUrl);

        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
