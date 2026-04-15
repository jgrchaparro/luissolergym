<?php

namespace App\Document;

use App\Repository\TipoMensualidadRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'tipo_mensualidad', repositoryClass: TipoMensualidadRepository::class)]
class TipoMensualidad
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fechaCreacion = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $codigo = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $descripcion = null;
    #[MongoDB\Field(type: 'float')]
    private float $monto = 0.0;

    public function __construct()
    {
        $this->fechaCreacion = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getFechaCreacion(): ?\DateTime
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(?\DateTime $fechaCreacion): void
    {
        $this->fechaCreacion = $fechaCreacion;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): void
    {
        $this->codigo = $codigo;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function setMonto(float $monto): void
    {
        $this->monto = $monto;
    }
}
