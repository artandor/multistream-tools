<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/connect/twitch', name: 'connect_twitch_start')]
    public function twitchConnect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // will redirect to Twitch !
        return $clientRegistry
            ->getClient('twitch') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'user:read:email', 'channel:manage:broadcast', 'channel:read:subscriptions',
            ], []);
    }

    #[Route(path: '/connect/twitch/check', name: 'connect_twitch_check')]
    public function twitchConnectCheck(Request $request, ClientRegistry $clientRegistry)
    {
    }

    #[Route(path: '/connect/google', name: 'connect_google_start')]
    public function googleConnect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // will redirect to Google !
        return $clientRegistry
            ->getClient('google') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'https://www.googleapis.com/auth/youtube',
                'https://www.googleapis.com/auth/youtube.force-ssl',
                'https://www.googleapis.com/auth/youtube.channel-memberships.creator',
            ], ['access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'true']);
    }

    #[Route(path: '/connect/google/check', name: 'connect_google_check')]
    public function googleConnectCheck(Request $request, ClientRegistry $clientRegistry)
    {
    }

    #[Route(path: '/connect/brime', name: 'connect_brime_start')]
    public function brimeConnect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // will redirect to Brime !
        return $clientRegistry
            ->getClient('brime') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'openid', 'email', 'offline_access',
            ], []);
    }

    #[Route(path: '/connect/brime/check', name: 'connect_brime_check')]
    public function brimeConnectCheck(Request $request, ClientRegistry $clientRegistry)
    {
    }

    #[Route(path: '/connect/trovo', name: 'connect_trovo_start')]
    public function trovoConnect(ClientRegistry $clientRegistry): RedirectResponse
    {
        // will redirect to Brime !
        return $clientRegistry
            ->getClient('trovo') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'user_details_self', 'channel_update_self',
            ], []);
    }

    #[Route(path: '/connect/trovo/check', name: 'connect_trovo_check')]
    public function trovoConnectCheck(Request $request, ClientRegistry $clientRegistry)
    {
    }
}
