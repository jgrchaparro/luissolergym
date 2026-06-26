<?php

namespace App\Document;

use App\Repository\ClienteRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'cliente', repositoryClass: ClienteRepository::class)]
class Cliente
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $cedula = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $nombres = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $apellidos = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $email = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $telefono = null;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fechaCreacion = null;
    #[MongoDB\Field(type: 'bool')]
    protected bool $activo = true;
    #[MongoDB\Field(type: 'bool')]
    protected bool $solvente = false;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fecha_corte = null;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fecha_vencimiento = null;
    #[MongoDB\ReferenceOne(targetDocument: TipoMensualidad::class)]
    protected ?TipoMensualidad $tipo_mensualidad = null;
    public function __construct()
    {
        $this->fechaCreacion = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCedula(): ?string
    {
        return $this->cedula;
    }

    public function setCedula(?string $cedula): self
    {
        $this->cedula = $cedula;
        return $this;
    }

    public function getNombres(): ?string
    {
        return $this->nombres;
    }

    public function setNombres(?string $nombres): self
    {
        $this->nombres = $nombres;
        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(?string $apellidos): self
    {
        $this->apellidos = $apellidos;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): self
    {
        $this->telefono = $telefono;
        return $this;
    }

    public function getFechaCreacion(): ?\DateTime
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(?\DateTime $fechaCreacion): self
    {
        $this->fechaCreacion = $fechaCreacion;
        return $this;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    public function isSolvente(): bool
    {
        return $this->solvente;
    }

    public function setSolvente(bool $solvente): void
    {
        $this->solvente = $solvente;
    }

    public function getFechaCorte(): ?\DateTime
    {
        return $this->fecha_corte;
    }

    public function setFechaCorte(?\DateTime $fecha_corte): void
    {
        $this->fecha_corte = $fecha_corte;
    }

    public function getFechaVencimiento(): ?\DateTime
    {
        return $this->fecha_vencimiento;
    }

    public function setFechaVencimiento(?\DateTime $fecha_vencimiento): void
    {
        $this->fecha_vencimiento = $fecha_vencimiento;
    }

    public function getTipoMensualidad(): ?TipoMensualidad
    {
        return $this->tipo_mensualidad;
    }

    public function setTipoMensualidad(?TipoMensualidad $tipo_mensualidad): void
    {
        $this->tipo_mensualidad = $tipo_mensualidad;
    }
}
