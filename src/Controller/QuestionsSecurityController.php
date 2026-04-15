<?php

namespace App\Controller;

use App\Document\UserQuestions;
use App\Form\Type\UserQuestionsType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QuestionsSecurityController extends AbstractController
{
    private $dm;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    #[Route('/security/questions/list', name: 'security_questions_list')]
    public function questionsListAction(Request $request): Response
    {
        $error = 0;
        $preguntas = $this->dm->getRepository(UserQuestions::class)->findAll();
        $pregunta = new UserQuestions();

        $form = $this->createForm(UserQuestionsType::class, $pregunta);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dm->persist($pregunta);
            $this->dm->flush();

            $this->addFlash('success', 'Pregunta de seguridad creada correctamente');

            return $this->redirectToRoute('security_questions_list');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $error = 1;
            $this->addFlash('error', 'Error al crear la pregunta de seguridad');
        }

        return $this->render(
            'security/questions.list.html.twig',
            [
                'preguntas' => $preguntas,
                'form' => $form->createView(),
                'error' => $error
            ]
        );
    }

    #[Route('/security/question/{id}/eliminar/', name: 'security_question_delete')]
    public function deleteQuestionAction(string $id): Response
    {
        $pregunta = $this->dm->getRepository(UserQuestions::class)->find($id);

        if (!$pregunta) {
            throw $this->createNotFoundException('Pregunta de seguridad no encontrada');
        }

        $this->dm->remove($pregunta);
        $this->dm->flush();

        $this->addFlash('success', 'Pregunta de seguridad eliminada correctamente');

        return $this->redirectToRoute('security_questions_list');
    }
}
