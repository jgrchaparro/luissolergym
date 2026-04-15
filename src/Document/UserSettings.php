<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'UserSettings')]

class UserSettings
{
    #[ODM\Id(strategy: 'auto')]
    protected string $id;
    #[ODM\Field (type:"int")]
    private $maximoIntentosPasswordErrado = 3;
    #[ODM\Field (type:"int")]
    private $minimoNumeroPreguntasSeguridadContestar;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getMaximoIntentosPasswordErrado()
    {
        return $this->maximoIntentosPasswordErrado;
    }

    /**
     * @param mixed $maximoIntentosPasswordErrado
     */
    public function setMaximoIntentosPasswordErrado($maximoIntentosPasswordErrado): void
    {
        $this->maximoIntentosPasswordErrado = $maximoIntentosPasswordErrado;
    }

    /**
     * @return mixed
     */
    public function getMinimoNumeroPreguntasSeguridadContestar()
    {
        return $this->minimoNumeroPreguntasSeguridadContestar;
    }

    /**
     * @param mixed $minimoNumeroPreguntasSeguridadContestar
     */
    public function setMinimoNumeroPreguntasSeguridadContestar($minimoNumeroPreguntasSeguridadContestar): void
    {
        $this->minimoNumeroPreguntasSeguridadContestar = $minimoNumeroPreguntasSeguridadContestar;
    }
}
