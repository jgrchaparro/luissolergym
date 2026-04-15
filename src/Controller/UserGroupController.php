<?php

namespace App\Controller;

use App\Document\User;
use App\Document\UserGroup;
use App\Document\UserRole;
use App\Form\Type\UserGroupType;
use App\Services\UserService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserGroupController extends AbstractController
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    #[Route('/security/group/list/', name: 'group_list')]
    public function listGroupAction(): Response
    {
        $groups = $this->documentManager->getRepository(UserGroup::class)->findAll();

        return $this->render(
            'security/group.list.html.twig',
            array(
                'grupos' => $groups
            )
        );
    }

    #[Route('/security/group/add/', name: 'group_add')]
    public function addGroupAction(Request $request): Response
    {
        $group = new UserGroup("");

        return $this->createGropupForm($group, $request);
    }

    #[Route('/security/group/{id}/edit/', name: 'group_edit')]
    public function editGroupAction(string $id, Request $request): Response
    {
        $group = $this->documentManager->getRepository(UserGroup::class)->find($id);

        return $this->createGropupForm($group, $request);
    }

    #[Route('/security/group/{id}/details/', name: 'group_detail')]
    public function detailsGroupAction(string $id): Response
    {
        $group = $this->documentManager->getRepository(UserGroup::class)->find($id);
        $roles = $this->documentManager->getRepository(UserRole::class)->findAll();

        return $this->render(
            'security/group.details.html.twig',
            array(
                'group' => $group,
                'roles' => $roles
            )
        );
    }

    #[Route('/security/group/check/', name: 'group_check', options: ['expose' => true])]
    public function checkGroupAction(Request $request, UserService $userService): JsonResponse
    {
        $get = $request->query->all();

        $userService->setUserGroup($get['userId'], $get['groupId']);

        $response = new JsonResponse();
        $response->setData($get);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);

        return $response;
    }

    #[Route('/security/group/{id}/eliminar/', name: 'group_eliminar', options: ['expose' => true])]
    public function groupEliminarIndex(Request $request, string $id): Response
    {
        /** @var UserGroup $grupo */
        $grupo = $this->documentManager->getRepository(UserGroup::class)->find($id);

        if (!$grupo) {
            throw $this->createNotFoundException('Grupo no encontrado');
        }

        $users = $this->documentManager->getRepository(User::class)->findBy(['groups' => $grupo]);

        foreach ($users as $user) {
            $user->removeGroup($grupo);
            $this->documentManager->persist($user);
        }

        $this->documentManager->remove($grupo);
        $this->documentManager->flush();

        return $this->redirectToRoute('group_list');
    }

    /**
     * @param UserGroup $group
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws MongoDBException
     */
    public function createGropupForm(UserGroup $group, Request $request): RedirectResponse|Response
    {
        $form = $this->createForm(UserGroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->documentManager->persist($group);
            $this->documentManager->flush();

            return $this->redirectToRoute(
                'group_detail',
                array(
                    'id' => $group->getId()
                )
            );
        }

        return $this->render(
            'security/group.add.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }
}
