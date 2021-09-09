<?php

namespace App\Security;

use App\Entity\Account;
use App\Entity\Platform;
use App\Entity\User;
use App\Provider\BrimeProvider;
use App\Provider\GoogleProvider;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Vasilvestre\Oauth2Brimetv\BrimeResourceOwner;
use Vertisan\OAuth2\Client\Provider\TwitchHelixResourceOwner;
use App\Provider\TwitchProvider;

class PlatformAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private UserPasswordHasherInterface $passwordEncoder;
    private Security $security;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router, UserPasswordHasherInterface $passwordEncoder, Security $security)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
    }

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return (
            $request->attributes->get('_route') === 'connect_twitch_check'
            || $request->attributes->get('_route') === 'connect_google_check'
            || $request->attributes->get('_route') === 'connect_brime_check'
        );
    }

    public function authenticate(Request $request): PassportInterface
    {
        $client = null;
        switch ($request->attributes->get('_route')) {
            case 'connect_twitch_check':
                $client = $this->clientRegistry->getClient('twitch');
                break;
            case 'connect_google_check':
                $client = $this->clientRegistry->getClient('google');
                break;
            case 'connect_brime_check':
                $client = $this->clientRegistry->getClient('brime');
                break;
            default:
                dump('This provider is not supported');
                break;
        }
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client, $request) {

                /** @var TwitchHelixResourceOwner|GoogleUser|BrimeResourceOwner $resourceOwner */
                $resourceOwner = $client->fetchUserFromToken($accessToken);

                // 1) have they logged in with Twitch before? Easy!
                /** @var Account $account */
                $account = $this->entityManager->getRepository(Account::class)->findOneBy(['externalId' => $resourceOwner->getId()]);

                $email = $resourceOwner->getEmail();

                if ($account) {
                    $account->setAccessToken($accessToken);
                    $account->setRefreshToken($accessToken->getRefreshToken());
                    $this->entityManager->flush();
                    return $account->getLinkedTo();
                }

                if ($this->security->getUser() instanceof User) {
                    $user = $this->security->getUser();
                } else {
                    $user = new User();
                    $user->setEmail($email);
                }

                $account = new Account();
                $account->setEmail($email);
                $platformRepository = $this->entityManager->getRepository(Platform::class);
                switch ($request->attributes->get('_route')) {
                    case 'connect_twitch_check':
                        $account->setPlatform($platformRepository->findOneBy(['provider' => TwitchProvider::class]));
                        break;
                    case 'connect_google_check':
                        $account->setPlatform($platformRepository->findOneBy(['provider' => GoogleProvider::class]));
                        break;
                    case 'connect_brime_check':
                        $account->setPlatform($platformRepository->findOneBy(['provider' => BrimeProvider::class]));
                        break;
                }

                $account->setAccessToken($accessToken);
                $account->setRefreshToken($accessToken->getRefreshToken());
                $account->setExternalId($resourceOwner->getId());
                $user->addAccount($account);
                $user->setPassword($this->passwordEncoder->hashPassword($user, md5(random_bytes(16))));
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
