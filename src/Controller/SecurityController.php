<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * @Route("/security", name="security")
     */
    public function index(): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Link to this controller to start the "connect" process
     *
     * @Route("/connect/twitch", name="connect_twitch_start")
     */
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        // will redirect to Discord !
        return $clientRegistry
            ->getClient('twitch') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'user:read:email', 'channel:manage:broadcast'
            ], []);
    }

    /**
     * After going to Discord, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/connect/twitch/check", name="connect_twitch_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
    }
}
