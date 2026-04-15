<?php

namespace App\EventListener;

use App\Events\UserLogEvent;
use App\Services\UserService;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class UserLogEventListener implements EventSubscriberInterface
{
    private $userService;

    /**
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            UserLogEvent::ON_LOGIN_FAIL => 'onLoginFail',
        );
    }

    /**
     * @throws MongoDBException
     */
    public function onLoginFail(UserLogEvent $event)
    {
        $userName = $event->getUserName();

        $this->userService->incLoginFail($userName);
    }
}