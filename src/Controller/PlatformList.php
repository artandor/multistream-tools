<?php

namespace App\Controller;

use App\Repository\PlatformRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/', name: 'home')]
class PlatformList extends AbstractController
{
    private PlatformRepository $platformRepository;

    public function __construct(PlatformRepository $platformRepository)
    {
        $this->platformRepository = $platformRepository;
    }

    public function __invoke(): Response
    {
        return $this->render('home/index.html.twig', [
            'platforms' => $this->platformRepository->findBy([
                'enabled' => true,
            ]),
        ]);
    }
}
