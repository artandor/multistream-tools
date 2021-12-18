<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/privacy-policy', name: 'privacy')]
final class PrivacyPolicy extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('privacy.html.twig');
    }
}
