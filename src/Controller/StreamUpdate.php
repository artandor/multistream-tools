<?php

namespace App\Controller;

use App\Entity\TitleHistory;
use App\Entity\User;
use App\Form\StreamInfoType;
use App\Provider\AbstractPlatformProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/update-stream-infos", name="updateStreamInfos")
 */
class StreamUpdate extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('home');
        }

        /** @var User $user */
        $user = $this->getUser();

        $streamTitle = new TitleHistory();
        $form = $this->createForm(StreamInfoType::class, $streamTitle);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $streamTitle->setCreator($user);
            foreach ($user->getAccounts() as $account) {
                if ($account->getPlatform()->isEnabled()) {
                    if (class_exists($account->getPlatform()->getProvider())) {
                        /** @var AbstractPlatformProvider $provider */
                        $provider = new ($account->getPlatform()->getProvider())($this->em, $this->logger);
                        if ($provider->updateStreamTitleAndCategory($account, $streamTitle->getTitle(), $streamTitle->getCategory())) {
                            $this->addFlash('titleUpdate-success', 'Successfully updated title for '.$account->getPlatform()->getName());
                        } else {
                            $this->addFlash('titleUpdate-failure', 'Failed to update title for '.$account->getPlatform()->getName().'. Try to authenticate again.');
                        }
                    } else {
                        $this->logger->error('This provider doesn\'t exist.');
                        $this->addFlash('titleUpdate-failure', 'Update title feature does not exist yet for '.$account->getPlatform()->getName());
                    }
                }
            }

            $this->em->persist($streamTitle);
            if (count($user->getTitleHistory()) > 10) {
                for ($i = 0; $i <= count($user->getTitleHistory()) - 10; ++$i) {
                    $user->getTitleHistory()->remove($i);
                }
            }
            $this->em->flush();
            $this->em->refresh($user); // Refresh title history
        }

        return $this->render('home/update-stream-infos.html.twig', [
            'form' => $form->createView(),
            'title_history' => $user->getLastTitles(),
        ]);
    }
}
