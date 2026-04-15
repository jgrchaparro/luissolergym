<?php

namespace App\Events;

use Symfony\Contracts\EventDispatcher\Event;

class UserLogEvent extends Event
{
    public const ON_LOGIN_FAIL = "on_login_fail";
    public const ON_LOGIN_SUCCESS = "on_login_success";
    public const ON_USER_ACTION = "on_user_action";

    private $userName;
    private $password;
    private $accion;

    /**
     * @param string $userName
     * @param string $password
     * @param string $accion
     */
    public function __construct(string $userName, string $password, string $accion)
    {
        $this->userName = $userName;
        $this->password = $password;
        $this->accion = $accion;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAccion(): string
    {
        return $this->accion;
    }

    public function setAccion(string $accion): void
    {
        $this->accion = $accion;
    }
}
