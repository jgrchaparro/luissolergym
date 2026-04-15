<?php

namespace App\Security\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface {
//    protected $userManager;
//    protected $dispatcher;
//
//    public function __construct(UserManagerInterface $userManager, EventDispatcherInterface $dispatcher){
//        $this->userManager = $userManager;
//        $this->dispatcher = $dispatcher;
//    }

    public function logout(Request $request, Response $response, TokenInterface $token): void
    {
        $clientIp = $request->getClientIp();
        $serverIp = gethostbyname(gethostname());

//        $event = new UserLogEvent($token->getUsername(), "",UserLogEvent::ON_LOGOUT_SUCCESS, $clientIp, $serverIp, "", $request->headers->all());
//        $this->dispatcher->dispatch(UserLogEvent::ON_LOGOUT_SUCCESS, $event);
    }
}
