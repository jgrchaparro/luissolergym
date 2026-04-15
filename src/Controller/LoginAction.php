<?php

namespace App\Controller;

use Captcha\Bundle\CaptchaBundle\Security\Core\Exception\InvalidCaptchaException;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Nucleos\UserBundle\Event\GetResponseLoginEvent;
use Nucleos\UserBundle\NucleosUserEvents;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\SecurityRequestAttributes; // Nuevo en Symfony 6.4+
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class LoginAction extends AbstractController
{
    private $twig;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CsrfTokenManagerInterface|null
     */
    private $tokenManager;

    public function __construct(
        Environment               $twig,
        EventDispatcherInterface  $eventDispatcher,
        CsrfTokenManagerInterface $tokenManager = null
    )
    {
        $this->twig = $twig;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenManager = $tokenManager;
    }

    public function __invoke(Request $request): Response
    {
        $event = new GetResponseLoginEvent($request);
        $this->eventDispatcher->dispatch($event, NucleosUserEvents::SECURITY_LOGIN_INITIALIZE);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $session = $this->getSession($request);

        // CAMBIO PRINCIPAL: Usar SecurityRequestAttributes en lugar de Security
        $authErrorKey = SecurityRequestAttributes::AUTHENTICATION_ERROR;
        $lastUsernameKey = SecurityRequestAttributes::LAST_USERNAME;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        // get the error if any (works with forward and redirect -- see below)

        if ($request->isMethod('POST')) {
            if (!$_ENV['MOSTRAR_CAPTCHA']) {
                return $this->redirectToRoute('nucleos_user_security_check', [
                    'request' => $request], 307);
            }

            // validate the user-entered Captcha code when the form is submitted
            $code = $request->request->get('captcha');
            $phrase = $request->request->get('phrase');
            $isHuman = $phrase === $code;

            if ($isHuman) {
                // Captcha validation passed, check username and password
                return $this->redirectToRoute('nucleos_user_security_check', [
                    'request' => $request], 307);
            } else {
                // Captcha validation failed, set an invalid captcha exception in $authErrorKey attribute
                $invalidCaptchaEx = new InvalidCaptchaException('Código invalido, intente nuevamente');
                $request->attributes->set($authErrorKey, $invalidCaptchaEx);

                // set last username entered by the user
                $username = $request->request->get('_username', null, true);
                $request->getSession()->set($lastUsernameKey, $username);
            }
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        $builder = null;
        if($_ENV['MOSTRAR_CAPTCHA']){
            $phraseBuilder = new PhraseBuilder(5, '0123456789');
            $builder = new CaptchaBuilder(null, $phraseBuilder);
            $builder->build();
        }

        return new Response($this->twig->render('@NucleosUser/Security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $this->getCsrfToken(),
            'builder' => $builder,
        ]));
    }

    private function getSession(Request $request): ?SessionInterface
    {
        return $request->hasSession() ? $request->getSession() : null;
    }

    private function getCsrfToken(): ?string
    {
        return null !== $this->tokenManager ? $this->tokenManager->getToken('authenticate')->getValue() : null;
    }
}
