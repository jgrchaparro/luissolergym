<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Bundle\MongoDBBundle\Validator\Constraints\Unique as MongoDBUnique;

#[ODM\Document(collection: 'UserQuestions')]
#[MongoDBUnique(fields:"description", message:"Ya existe una pregunta con esta descripción")]
class UserQuestions
{
    #[ODM\Id]
    protected $id;
    #[ODM\Field (type:"string")]
    protected ?string $description = null;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
