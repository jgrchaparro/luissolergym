<?php

namespace App\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\Form\FormError;
use App\Document\User;
use App\Document\UserAnswers;
use App\Document\UserGroup;
use App\Events\MovilnetEvents;
use App\Form\Type\UserAnswerType;
use App\Form\Type\UserRecoveryPasswordType;
use App\Form\Type\UserType;
use App\Services\ServiceLog;
use App\Services\UserService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Nucleos\UserBundle\Model\UserManager;
use Nucleos\UserBundle\Util\SimpleTokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $userManager;
    private $security;
    private $serviceLog;
    private $userService;
    private $dm;

    public function __construct(DocumentManager $dm, UserManager $userManager, Security $security,
                                UserService $userService, UserPasswordHasherInterface $passwordHasher
    )

    {
        $this->dm = $dm;
        $this->userManager = $userManager;
        $this->security = $security;
        $this->userService = $userService;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/usuarios/listar', name: 'usuarios_index')]
    public function usuariosAction(): Response
    {
        return $this->render(
            'security/user.list.html.twig'
        );
    }

    #[Route('/usuarios/consultaDT', name: 'usuarios_listar', options: ['expose' => true])]
    public function usuariosListarDTAction(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $btnDetalle = '';
        if ($user->hasRole('ROLE_USER_DETAILS')) {
            $btnDetalle = '<button type="button" class="btn btn-default btn-user-detalle btn-sm pull-right">Detalle</button>';
        }

        $get = $request->query->all();

        $busqueda = $get['columns'][0]['search']['value'];

        $qb = $this->dm->createQueryBuilder(User::class);

        if (isset($busqueda) && $busqueda != '') {
            $qb->addOr($qb->expr()->field('username')->equals(new \MongoDB\BSON\Regex($busqueda, 'i')));
            $qb->addOr($qb->expr()->field('email')->equals(new \MongoDB\BSON\Regex($busqueda, 'i')));
            $qb->addOr($qb->expr()->field('nombre')->equals(new \MongoDB\BSON\Regex($busqueda, 'i')));
        }

        $qb2 = clone $qb;
        $count = $qb2->count()->getQuery()->execute();

        if (isset($get['length'])) {
            $limit = $get['length'];
            $qb->limit($limit);
        }
        if (isset($get['start'])) {
            $skip = $get['start'];
            $qb->skip($skip);
        }

        $usuarios = $qb->getQuery()->execute();

        $output = [];
        /**
         * @var $user User
         */
        foreach ($usuarios as $user) {
            $data[0] = $user->getId();
            $data[1] = $user->getUsername();
            $data[2] = $user->getNombre();
            $data[3] = $user->getEmail();
            $data[4] = $user->getCedula();
            $data[5] = $user->getTelefono();
            $data[6] = $user->getUnidad();
            $data[7] = implode(',', $user->getGroupNames());
            $data[8] = $user->isEnabled() ? '<i class="fa fa-check text-success pull-right"></i>' : '';
            $data[9] = $btnDetalle;

            $output[] = $data;
        }

        $response = array(
            "draw" => intval($get['draw']),
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $output
        );

        return new JsonResponse($response);
    }

    #[Route('/usuario/perfil/{id}', name: 'usuario_perfil_editar', options: ['expose' => true], defaults: ['id' => ''])]
    public function usuarioPerfilEditarAction(Request $request, $id)
    {
        if ($id == '') {
            $user = $this->getUser();
            $id = $user->getId();
        }
        /** @var $user User */
        $user = $this->dm->getRepository(User::class)->find($id);

        return $this->createFormUser($user, $request, $user->getId());
    }

    #[Route('/usuarios/{id}/editar', name: 'usuario_editar', options: ['expose' => true], defaults: ['id' => ''])]
    public function usuarioEditarAction(Request $request, $id)
    {
        $user = $this->dm->getRepository(User::class)->find($id);
        return $this->createFormUser($user, $request, $id);
    }

    #[Route('/usuarios/crear', name: 'usuario_registrar', options: ['expose' => true])]
    public function usuarioRegistrarAction(Request $request)
    {
        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $question = $form->get('questions')->getData();
            $answer = $form->get('answer')->getData();
            $answerUser = new UserAnswers();

            $answerUser->setQuestion($question->getDescription());
            $answerUser->setAnswer($answer);

            $user->addAnswerUser($answerUser);

            try {
                $this->userManager->updateUser($user);

                $this->addFlash('success', 'Usuario creado exitosamente');
                return $this->redirectToRoute('usuarios_index');

            } catch (MongoDBException $e) {
                $errorMessage = $e->getMessage();

                if (strpos($errorMessage, 'emailCanonical_1') !== false) {
                    $form->get('email')->addError(new FormError('Este correo electrónico ya está registrado'));
                } elseif (strpos($errorMessage, 'usernameCanonical_1') !== false) {
                    $form->get('username')->addError(new FormError('Este nombre de usuario ya está registrado'));
                } else {
                    $form->addError(new FormError('Error al guardar el usuario. Por favor intente nuevamente.'));
                }
                $this->serviceLog->setLog('ERROR_CREAR_USUARIO: ' . $errorMessage);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error inesperado: ' . $e->getMessage()));
                $this->serviceLog->setLog('ERROR_CREAR_USUARIO: ' . $e->getMessage());
            }
        }

        return $this->render(
            'security/user.register.html.twig',
            array(
                'user' => $user,
                'form' => $form->createView(),
            )
        );
    }

    #[Route('/usuarios/{id}/eliminar}', name: 'usuario_eliminar', options: ['expose' => true])]
    public function usuarioEliminarAction($id)
    {
        /** @var $user User */
        $user = $this->dm->getRepository(User::class)->find($id);

        if ($user) {
            $this->serviceLog->setLog(MovilnetEvents::ELIMINAR_USUARIO);
            $user->setEnabled(false);
            $this->dm->persist($user);
            $this->dm->flush();

            return $this->redirectToRoute('usuarios_index');
        }

        return $this->render(
            'security/user.list.html.twig'
        );
    }

    #[Route('/usuarios/{id}/details', name: 'user_details', options: ['expose' => true], defaults: ['id' => ''])]
    public function detailsUserAction($id): Response
    {
        $user = $this->dm->getRepository(User::class)->find($id);
        $grupos = $this->dm->getRepository(UserGroup::class)->findAll();

        return $this->render(
            'security/user.details.html.twig',
            array(
                'user' => $user,
                'grupos' => $grupos
            )
        );
    }

    #[Route('/usuarios/{id}/change-enabled', name: 'user_change_enabled')]
    public function changeEnabledUserAction($id): RedirectResponse
    {
        $this->userService->enabledUser($id);

        return $this->redirectToRoute('user_details', ['id' => $id]);
    }

    #[Route('/usuarios/{id}/eliminar-pregunta{nameQuestion}', name: 'user_eliminar_pregunta')]
    public function eliminarPreguntaSeguridadAction($id, $nameQuestion): RedirectResponse
    {
        $user = $this->dm->getRepository(User::class)->find($id);

        $preguntas = $user->getAnswerUser();

        /** @var UserAnswers $pregunta */
        foreach ($preguntas as $pregunta) {
            if (strcmp($pregunta->getQuestion(), $nameQuestion) === 0) {
                $user->removeAnswerUser($pregunta);
                $this->userManager->updateUser($user);
                break;
            }
        }

        return $this->redirectToRoute('usuario_perfil_editar', ['id' => $id]);
    }

    #[Route('/usuarios/login/recuperacion/password/{username}', name: 'user_login_recuperacion_password', defaults: ['username' => ''])]
    public function recuperarPasswordAction($username): Response
    {
        return $this->render('security/user.recovery.password.html.twig');
    }

    #[Route('/usuarios/recuperacion/validacion-usuario', name: 'user_recuperacion_validacion_usuario')]
    public function validacionUsuarioAction(): RedirectResponse
    {
        $username = $_POST['_username'];
        $user = $this->userManager->findUserByUsername($_POST['_username']);
        if (!$user) {
            $this->addFlash('danger', 'Usuario no registrado');
            return $this->redirectToRoute('user_login_recuperacion_password', ['username' => $username]);
        }

        return $this->redirectToRoute('user_recuperacion_responder_preguntas', ['username' => $user->getUsername()]);
    }

    #[Route('/usuarios/recuperacion/reponder-preguntas-seguridad/{username}', name: 'user_recuperacion_responder_preguntas')]
    public function responderPreguntasAction($username): Response
    {
        /** @var User $user */
        $user = $this->userManager->findUserByUsername($username);
        $preguntasRepuestas = $user->getAnswerUser();
        $numeroPreguntas = count($preguntasRepuestas);

        $userSettings = $this->userService->getUserSettings();
        if ($numeroPreguntas < $userSettings->getMinimoNumeroPreguntasSeguridadContestar()) {
            $this->addFlash('danger', 'Usuario no posee preguntas de seguridad, comunicarse con el administrador.');
            return $this->redirectToRoute('user_login_recuperacion_password', ['username' => $username]);
        }
        return $this->render('security/user.responder.preguntas.html.twig',
            [
                'preguntasRepuestas' => $preguntasRepuestas,
                'numeroPreguntas' => $numeroPreguntas,
                'username' => $user->getUsername()
            ]
        );
    }

    #[Route('/usuarios/validacion/respuestas', name: 'user_validacion_respuestas', options: ['expose' => true])]
    public function validarRespuestasAction(Request $request, SimpleTokenGenerator $tokenGenerator, SessionInterface $session): JsonResponse
    {
        $flag = true;

        $token = '';

        $data = $request->query->all();

        $respuestasUsuario = $data['respuesta'];

        $user = $this->userManager->findUserByUsername($data['username']);

        $respuestasCorrectas = $user->getAnswerUser();

        foreach ($respuestasUsuario as $indice => $respuestaUsuario) {
            if (strcasecmp($respuestasCorrectas[$indice]->getAnswer(), $respuestaUsuario) !== 0) {
                $flag = false;
                break;
            }
        }

        if ($flag) {
            $token = $tokenGenerator->generateToken();
            $session->set('token', $token);
        }
        return new JsonResponse(['response' => $flag, 'token' => $token]);
    }

    #[Route('/usuarios/fail/validacion/respuestas/{username}', name: 'user_fail_validacion_respuestas', options: ['expose' => true])]
    public function validarRespuestasFailAction($username): RedirectResponse
    {
        $this->addFlash('danger', 'La información proporcionada es incorrecta');
        return $this->redirectToRoute('user_login_recuperacion_password', ['username' => $username]);
    }

    #[Route('/usuarios/success/validacion/respuestas/{username}/{token}', name: 'user_success_validacion_respuestas', options: ['expose' => true])]
    public function validarRespuestasSuccessAction(Request $request, $username, $token, SessionInterface $session): Response
    {
        $tokenSesion = $session->get('token');
        if (!$tokenSesion || strcmp($tokenSesion, $token) !== 0) {
            return $this->redirectToRoute('nucleos_user_security_login');
        }
        $user = $this->userManager->findUserByUsername($username);
        $form = $this->createForm(UserRecoveryPasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $session->remove('token');
            $data = $request->request->all();
            $newPassword = $data['user_recovery_password']['plainPassword']['first'];
            $user->setPlainPassword($newPassword);
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'Contraseña actualizada exitosamente.');
            return $this->redirectToRoute('nucleos_user_security_login');
        }
        return $this->render('security/user.reset.password.htm.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    #[Route('/usuarios/validar-numero-respuestas/preguntas_seguridad', name: 'usuarios_validar_numero_respuestas_preguntas_seguridad', options: ['expose' => true])]
    public function validarNumeroRespuestasSeguridadAction(Request $request, SessionInterface $session): Response
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('nucleos_user_security_login');
        }
        $session->set('showNotificacion', false);
        $flag = true;
        /** @var User $user */
        $user = $this->getUser();
        $userSettings = $this->userService->getUserSettings();
        $minimoPreguntasContestadas = $userSettings->getMinimoNumeroPreguntasSeguridadContestar();
        $userPreguntasSeguridadContestadas = count($user->getAnswerUser());

        if ($userPreguntasSeguridadContestadas < $minimoPreguntasContestadas) {
            $flag = false;
        }

        return new JsonResponse(['response' => $flag]);
    }

    /**
     * @param User $user
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     * @throws MongoDBException
     */
    public function createFormUser(User $user, Request $request, $id)
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        $userQuestions = $user->getAnswerUser();

        $answer = new UserAnswers();
        $formAnswer = $this->createForm(UserAnswerType::class, $answer);
        $formAnswer->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Restaurar valores de campos no editables
            if ($user->getId()) {
                $originalUser = $this->dm->getRepository(User::class)->find($user->getId());

                if ($originalUser) {
                    $user->setUsername($originalUser->getUsername());

                    if (!$this->security->isGranted('ROLE_USER_EDIT_EMAIL')) {
                        $user->setEmail($originalUser->getEmail());
                    }

                    if (!$this->security->isGranted('ROLE_USER_EDIT_NOMBRE')) {
                        $user->setNombre($originalUser->getNombre());
                    }

                    if (!$this->security->isGranted('ROLE_USER_EDIT_CEDULA')) {
                        $user->setCedula($originalUser->getCedula());
                    }

                    if (!$this->security->isGranted('ROLE_USER_EDIT_TELEFONO')) {
                        $user->setTelefono($originalUser->getTelefono());
                    }

                    if (!$this->security->isGranted('ROLE_USER_EDIT_UNIDAD')) {
                        $user->setUnidad($originalUser->getUnidad());
                    }
                }

                // Si el password está vacío, no actualizar
                if (empty($user->getPlainPassword())) {
                    $user->setPlainPassword(null);
                } else {
                    // ⭐ Hashear el nuevo password
                    $plainPassword = $user->getPlainPassword();
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
            }

            // ⭐ CAPTURAR ERRORES DE DUPLICATE KEY
            try {
                $this->userManager->updateUser($user);

                $this->addFlash('success', 'Datos Guardados!!!');
                return $this->redirectToRoute('usuario_editar', ['id' => $id]);

            } catch (MongoDBException $e) {
                $errorMessage = $e->getMessage();

                if (strpos($errorMessage, 'emailCanonical_1') !== false) {
                    $form->get('email')->addError(new FormError('Este correo electrónico ya está registrado'));
                } elseif (strpos($errorMessage, 'usernameCanonical_1') !== false) {
                    $form->get('username')->addError(new FormError('Este nombre de usuario ya está registrado'));
                } else {
                    $form->addError(new FormError('Error al guardar el usuario. Por favor intente nuevamente.'));
                }

                $this->serviceLog->setLog('ERROR_EDITAR_USUARIO: ' . $errorMessage);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error inesperado: ' . $e->getMessage()));
                $this->serviceLog->setLog('ERROR_EDITAR_USUARIO: ' . $e->getMessage());
            }
        }

        if ($formAnswer->isSubmitted() && $formAnswer->isValid()) {
            $this->denyAccessUnlessGranted('ROLE_ANSWER_SECURITY_QUESTIONS_PERFIL_USUARIO');

            $data = new UserAnswers();
            $data->setAnswer($answer->getAnswer());
            $data->setQuestion($answer->getQuestion()->getDescription());

            foreach ($userQuestions as $question) {
                if (strcmp($question->getQuestion(), $answer->getQuestion()->getDescription()) === 0) {
                    $user->removeAnswerUser($question);
                    break;
                }
            }

            $user->addAnswerUser($data);

            try {
                $this->userManager->updateUser($user);
                return $this->redirectToRoute('usuario_perfil_editar', ['id' => $id]);
            } catch (\Exception $e) {
                $formAnswer->addError(new FormError('Error al guardar la pregunta de seguridad'));
            }
        }

        return $this->render(
            'security/user.edit.html.twig',
            [
                'form' => $form->createView(),
                'formAnswer' => $formAnswer->createView(),
                'user' => $user,
                'userQuestions' => $userQuestions
            ]
        );
    }
}
