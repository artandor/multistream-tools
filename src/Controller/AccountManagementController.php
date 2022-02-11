<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ModeratorType;
use App\Repository\PlatformRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountManagementController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $em)
    {
    }

    #[Route('/account', name: 'account')]
    public function account(Request $request, PlatformRepository $platformRepository): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('home');
        }
        $moderatorForm = $this->createForm(ModeratorType::class);

        $moderatorForm->handleRequest($request);
        if ($moderatorForm->isSubmitted() && $moderatorForm->isValid()) {
            $data = $moderatorForm->getData();

            if ($data['moderatorEmail']) {
                $newModerator = $this->userRepository->findOneBy(['email' => $data['moderatorEmail']]);

                if ($newModerator) {
                    /** @var User $me */
                    $me = $this->getUser();
                    $me->addModerator($newModerator);
                    $this->em->flush();
                    $this->addFlash('notice', 'User successfully added to your moderators.');
                } else {
                    $this->addFlash('warning', 'Could not find user.');
                }
            }
        }

        return $this->render('account_management/index.html.twig', [
            'platforms' => $platformRepository->findBy([
                'enabled' => true,
            ]),
            'moderatorForm' => $moderatorForm->createView(),
        ]);
    }

    #[Route('/account/moderator/delete/{moderator}', name: 'account_moderator_delete')]
    public function delete(User $moderator): Response
    {
        /** @var User $me */
        $me = $this->getUser();
        $me->removeModerator($moderator);
        $this->em->flush();

        return $this->redirectToRoute('account');
    }
}
