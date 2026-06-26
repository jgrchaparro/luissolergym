<?php

namespace App\Security\Handler;

use App\Services\UserService;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $urlGenerator;
    private $userService;
    protected $dispatcher;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param EventDispatcherInterface $dispatcher
     * @param UserService $userService
     */
    public function __construct(
        UrlGeneratorInterface    $urlGenerator,
        EventDispatcherInterface $dispatcher,
        UserService              $userService
    )
    {
        $this->urlGenerator  = $urlGenerator;
        $this->dispatcher    = $dispatcher;
        $this->userService   = $userService;
    }

    /**
     * @throws MongoDBException
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $session = $request->getSession();

        $data = $request->request->all();
        $response = $this->userService->checkEnableUser($data['_username']);

        if (!$response) {
            $session->getFlashBag()->add('danger', 'Tu Usuario ha sido bloqueado, por favor comuníquese con el administrador del sistema');
            return new RedirectResponse($this->urlGenerator->generate('nucleos_user_security_login'));
        } else {
            $session->set('showNotificacion', true);
            $this->userService->resetLoginFail($data['_username']);


            return new RedirectResponse($this->urlGenerator->generate('pago_list'));
        }
    }
}
