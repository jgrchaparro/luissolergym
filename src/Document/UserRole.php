<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'UserRole')]
class UserRole
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    protected ?string $role = null;

    #[MongoDB\Field(type: 'string')]
    protected ?string $descripcion = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'role' => $this->getRole(),
            'description' => $this->getDescripcion(),
        ];
    }
}
