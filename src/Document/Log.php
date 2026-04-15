<?php

namespace App\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'log')]

class Log
{
    #[ODM\Id]
    protected $id;
    #[ODM\Field (type:"date")]
    protected $fecha;
    #[ODM\Field (type:"string")]
    protected $user;
    #[ODM\Field (type:"string")]
    protected $accion;
    #[ODM\Field (type:"string")]
    protected $schema_version;

    /**
     * Log constructor.
     * @param $fecha
     */
    public function __construct()
    {
        $this->fecha = new \MongoDB\BSON\UTCDateTime();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFecha()
    {
        return $this->fecha;
    }

    /**
     * @param mixed $fecha
     */
    public function setFecha($fecha): void
    {
        $this->fecha = $fecha;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getAccion()
    {
        return $this->accion;
    }

    /**
     * @param mixed $accion
     */
    public function setAccion($accion): void
    {
        $this->accion = $accion;
    }

    /**
     * @return mixed
     */
    public function getSchemaVersion()
    {
        return $this->schema_version;
    }

    /**
     * @param mixed $schema_version
     */
    public function setSchemaVersion($schema_version): void
    {
        $this->schema_version = $schema_version;
    }
}
