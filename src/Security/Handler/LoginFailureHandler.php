<?php

namespace App\Security\Handler;

use App\Events\UserLogEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    protected $urlGenerator;
    protected $dispatcher;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        UrlGeneratorInterface    $urlGenerator,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->dispatcher   = $dispatcher;
    }

    /**
     * This is called when an interactive authentication attempt fails. This is
     * called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return RedirectResponse The response to return, never null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        // Obtener la sesión directamente desde el request
        $session = $request->getSession();

//        $session->set(Security::AUTHENTICATION_ERROR, $exception);

        $data = $request->request->all();
        $event = new UserLogEvent($data['_username'], $data['_password'], UserLogEvent::ON_LOGIN_FAIL);
        $this->dispatcher->dispatch($event, UserLogEvent::ON_LOGIN_FAIL);

        // Opcional: agregar mensaje flash
        $session->getFlashBag()->add('danger', 'Credenciales inválidas. Por favor intenta nuevamente.');

        return new RedirectResponse($this->urlGenerator->generate('nucleos_user_security_login'));
    }
}
