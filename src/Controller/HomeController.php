<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\StreamInfoType;
use App\Provider\AbstractPlatformProvider;
use App\Repository\PlatformRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private PlatformRepository $platformRepository;

    public function __construct(PlatformRepository $platformRepository)
    {
        $this->platformRepository = $platformRepository;
    }

    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'platforms' => $this->platformRepository->findAll()
        ]);
    }

    /**
     * @Route("/update-stream-infos", name="updateStreamInfos")
     */
    public function updateStreamInfos(Request $request, LoggerInterface $logger, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(StreamInfoType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $streamInfos = $form->getData();

            // TODO : Add title and category to session so that it stays even after refresh of the page :)

            /** @var User $user */
            $user = $this->getUser();
            foreach ($user->getAccounts() as $account) {
                if (class_exists($account->getPlatform()->getProvider())) {
                    /** @var AbstractPlatformProvider $provider */
                    $provider = new ($account->getPlatform()->getProvider())($em, $logger);
                    if ($provider->updateStreamTitleAndCategory($account, $streamInfos['title'], $streamInfos['category'])) {
                        $this->addFlash('titleUpdate-success', 'Successfully updated title for ' . $account->getPlatform()->getName());
                    } else {
                        $this->addFlash('titleUpdate-failure', 'Failed to update title for ' . $account->getPlatform()->getName());

                    }
                } else {
                    $logger->error('This provider doesn\'t exist.');
                    $this->addFlash('titleUpdate-failure', 'Update title feature does not exist yet for ' . $account->getPlatform()->getName());
                }
            }
        }

        return $this->render('home/update-stream-infos.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
