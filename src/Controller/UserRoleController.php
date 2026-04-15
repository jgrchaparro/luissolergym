<?php

namespace App\Controller;

use App\Document\UserRole;
use App\Form\Type\UserRoleType;
use App\Services\UserGroupService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserRoleController extends AbstractController
{
    private $dm;
    private $serviceLog;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    #[Route('/security/role/add/', name: 'role_add')]
    public function addRoleAction(Request $request, DocumentManager $documentManager): RedirectResponse|Response
    {
        $role = new UserRole();

        return $this->roleForm($role, $request, $documentManager, false);
    }

    #[Route('/security/role/{id}/edit/', name: 'role_edit')]
    public function editRoleAction(string $id, Request $request, DocumentManager $documentManager): RedirectResponse|Response
    {
        $role = $documentManager->getRepository(UserRole::class)->find($id);

        if (!$role) {
            throw $this->createNotFoundException('Rol no encontrado');
        }

        return $this->roleForm($role, $request, $documentManager, true);
    }

    #[Route('/security/role/{id}/details/', name: 'role_detail')]
    public function detailsRoleAction(string $id, DocumentManager $documentManager): Response
    {
        $role = $documentManager->getRepository(UserRole::class)->find($id);

        if (!$role) {
            throw $this->createNotFoundException('Rol no encontrado');
        }

        return $this->render(
            'security/role.details.html.twig',
            [
                'role' => $role
            ]
        );
    }

    #[Route('/security/role/list/', name: 'role_list')]
    public function listRoleAction(DocumentManager $documentManager): Response
    {
        $roles = $documentManager->getRepository(UserRole::class)->findAll();

        return $this->render(
            'security/role.list.html.twig',
            [
                'roles' => $roles
            ]
        );
    }

    /**
     * @throws MongoDBException
     */
    private function roleForm(UserRole $role, Request $request, DocumentManager $documentManager, bool $isEdit = false): RedirectResponse|Response
    {
        $form = $this->createForm(UserRoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $documentManager->persist($role);
            $documentManager->flush();

            $this->addFlash('success', $isEdit ? 'Rol actualizado correctamente' : 'Rol creado correctamente');

            return $this->redirectToRoute(
                'role_detail',
                [
                    'id' => $role->getId()
                ]
            );
        }

        return $this->render(
            'security/role.add.html.twig',
            [
                'role' => $role,
                'form' => $form->createView(),
                'isEdit' => $isEdit
            ]
        );
    }

    #[Route('/security/role/check/', name: 'role_check', options: ['expose' => true])]
    public function checkRoleAction(Request $request, UserGroupService $userGroupService): JsonResponse
    {
        $get = $request->query->all();
        $userGroupService->setRoleGroup($get['roleId'], $get['groupId']);

        return new JsonResponse($get, Response::HTTP_OK);
    }

    #[Route('/security/role/{id}/eliminar/', name: 'role_eliminar', options: ['expose' => true])]
    public function releEliminarIndex(Request $request, string $id): Response
    {
        $role = $this->dm->getRepository(UserRole::class)->find($id);

        if (!$role) {
            throw $this->createNotFoundException('Rol no encontrado');
        }

        $this->dm->remove($role);
        $this->dm->flush();

        $this->addFlash('success', 'Rol eliminado correctamente');

        return $this->redirectToRoute('role_list');
    }
}
