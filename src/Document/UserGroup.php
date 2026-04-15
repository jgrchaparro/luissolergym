<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Nucleos\UserBundle\Model\Group as BaseGroup;

#[MongoDB\Document(collection: 'UserGroup')]
class UserGroup extends BaseGroup
{
    #[MongoDB\Id(strategy: 'auto')]
    protected $id;

    #[MongoDB\Field(type: 'string')]
    protected string $name;

    #[MongoDB\Field(type: 'collection')]
    protected array $roles = [];

    public function __construct(string $name, array $roles = [])
    {
        parent::__construct($name, $roles);
        $this->name = $name;
        $this->roles = $roles;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles ?? [];
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
