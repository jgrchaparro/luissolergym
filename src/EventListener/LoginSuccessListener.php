<?php


namespace App\EventListener;


use Nucleos\UserBundle\Event\FormEvent;
use Nucleos\UserBundle\NucleosUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginSuccessListener implements EventSubscriberInterface
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NucleosUserEvents::SECURITY_LOGIN_COMPLETED => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(FormEvent $event): void
    {
        $url = $this->router->generate('index');

        $event->setResponse(new RedirectResponse($url));
    }
}