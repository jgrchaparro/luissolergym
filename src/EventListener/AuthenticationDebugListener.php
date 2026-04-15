<?php
// src/EventListener/AuthenticationDebugListener.php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationDebugListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $this->logger->info('LOGIN SUCCESS', [
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'class' => get_class($user)
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $this->logger->error('LOGIN FAILED', [
            'message' => $exception->getMessage(),
            'class' => get_class($exception)
        ]);
    }
}
