<?php

namespace App\Document;

use App\Repository\MovimientosRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'movimientos', repositoryClass: MovimientosRepository::class)]
class Movimientos
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $tipo_mvto = null;
    #[MongoDB\Field(type: 'float')]
    private float $monto = 0.0;
    #[MongoDB\ReferenceOne(targetDocument: Cliente::class, cascade: ['persist'])]
    protected ?Cliente $cliente = null;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fechaCreacion = null;
    #[MongoDB\ReferenceOne(targetDocument: TipoPago::class)]
    protected ?TipoPago $tipo_pago = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $observaciones = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $moneda = 'USD';
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fechaVencimiento = null;
    #[MongoDB\Field(type: 'float')]
    protected ?float $montoUsdRef = null;
    #[MongoDB\Field(type: 'string')]
    protected ?string $clienteOcasional = null;


    public function __construct()
    {
        $this->fechaCreacion = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTipoMvto(): ?string
    {
        return $this->tipo_mvto;
    }

    public function setTipoMvto(?string $tipo_mvto): void
    {
        $this->tipo_mvto = $tipo_mvto;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function setMonto(float $monto): void
    {
        $this->monto = $monto;
    }

    public function getCliente(): ?Cliente
    {
        return $this->cliente;
    }

    public function setCliente(?Cliente $cliente): void
    {
        $this->cliente = $cliente;
    }

    public function getFechaCreacion(): ?\DateTime
    {
        return $this->fechaCreacion;
    }

    public function setFechaCreacion(?\DateTime $fechaCreacion): void
    {
        $this->fechaCreacion = $fechaCreacion;
    }

    public function getTipoPago(): ?TipoPago
    {
        return $this->tipo_pago;
    }

    public function setTipoPago(?TipoPago $tipo_pago): void
    {
        $this->tipo_pago = $tipo_pago;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    public function getMoneda(): ?string
    {
        return $this->moneda ?: 'USD';
    }

    public function setMoneda(?string $moneda): void
    {
        $this->moneda = $moneda ?: 'USD';
    }

    public function getFechaVencimiento(): ?\DateTime
    {
        return $this->fechaVencimiento;
    }

    public function setFechaVencimiento(?\DateTime $fechaVencimiento): void
    {
        $this->fechaVencimiento = $fechaVencimiento;
    }

    public function getMontoUsdRef(): ?float
    {
        return $this->montoUsdRef;
    }

    public function setMontoUsdRef(?float $montoUsdRef): void
    {
        $this->montoUsdRef = $montoUsdRef;
    }

    public function getClienteOcasional(): ?string
    {
        return $this->clienteOcasional;
    }

    public function setClienteOcasional(?string $clienteOcasional): void
    {
        $this->clienteOcasional = $clienteOcasional;
    }
}
