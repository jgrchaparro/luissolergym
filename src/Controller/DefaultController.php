<?php

namespace App\Controller;

use App\Events\MovilnetEvents;
use App\Services\ServiceLog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]

    public function indexAction():RedirectResponse
    {
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/dashboard', name: 'dashboard')]

    public function superDashboardAcction(SessionInterface $session): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $showNotificacion = $session->get('showNotificacion');

        return $this->render(
            'Default/index.html.twig',
            [
                'showNotificacion'=>$showNotificacion
            ]
        );
    }
}
