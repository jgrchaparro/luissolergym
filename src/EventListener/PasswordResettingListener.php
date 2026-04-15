<?php
/**
 * Created by PhpStorm.
 * User: DESARROLLO 1
 * Date: 13/12/2020
 * Time: 6:09 PM
 */

namespace App\EventListener;

use Nucleos\UserBundle\Event\FormEvent;
use Nucleos\UserBundle\NucleosUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class PasswordResettingListener implements EventSubscriberInterface
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NucleosUserEvents::RESETTING_RESET_SUCCESS => 'onPasswordResettingSuccess',
        ];
    }

    public function onPasswordResettingSuccess(FormEvent $event): void
    {
        $url = $this->router->generate('index');

        $event->setResponse(new RedirectResponse($url));
    }
}