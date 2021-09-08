<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\StreamInfoType;
use App\Provider\PlatformProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @Route("/update-stream-infos", name="updateStreamInfos")
     */
    public function updateStreamInfos(Request $request, LoggerInterface $logger): Response
    {
        $form = $this->createForm(StreamInfoType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $streamInfos = $form->getData();

            /** @var User $user */
            $user = $this->getUser();

            foreach ($user->getAccounts() as $account) {
                if (class_exists($account->getPlatformName())) {
                    /** @var PlatformProviderInterface $provider */
                    $provider = $account->getPlatformName();
                    if ($provider::updateStreamTitleAndCategory($account, $streamInfos['title'], $streamInfos['category'])) {
                        // TODO : Send a success notification
                        dump('Successfully updated title for ' . $account->getPlatformName());
                    } else {
                        // TODO : Send an error notification
                        dump('Failure while updated title for ' . $account->getPlatformName());
                    }
                } else {
                    $logger->error('This provider doesn\'t exist.');
                    // TODO : Notifier que le provider n'existe pas
                }
            }
        }

        return $this->render('home/update-stream-infos.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
