<?php

namespace App\Document;

use App\Repository\TipoPagoRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'tipo_pago_cuentas', repositoryClass: TipoPagoRepository::class)]
class TipoPago
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fechaCreacion = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $codigo = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $descripcion = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $numero = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $titular = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $rif = null;

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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): void
    {
        $this->numero = $numero;
    }

    public function getRif(): ?string
    {
        return $this->rif;
    }

    public function setRif(?string $rif): void
    {
        $this->rif = $rif;
    }

    public function getTitular(): ?string
    {
        return $this->titular;
    }

    public function setTitular(?string $titular): void
    {
        $this->titular = $titular;
    }
}
