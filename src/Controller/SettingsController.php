<?php

namespace App\Controller;

use App\Document\Settings;
use App\Form\Type\SettingsType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/settings/', name: 'settings_index')]
    public function settingsIndexAction(): Response
    {
        return $this->render('Settings/settings.index.html.twig');
    }

    #[Route('/settings/general/', name: 'settings_general')]
    public function settingsGeneralAction(Request $request): Response
    {
        $settings = $this->documentManager->getRepository(Settings::class)->findOneBy([]);

        if (!$settings) {
            $settings = new Settings();
        }

        $form = $this->createForm(SettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->documentManager->persist($settings);
            $this->documentManager->flush();

            $this->addFlash('success', 'Configuración guardada exitosamente.');

            return $this->redirectToRoute('settings_general');
        }

        return $this->render('Settings/settings.general.html.twig', [
            'form' => $form->createView(),
            'settings' => $settings,
        ]);
    }
}
