<?php

namespace App\Document;

use App\Repository\TasasRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'tasas', repositoryClass: TasasRepository::class)]
class Tasas
{
    #[MongoDB\Id(strategy: 'auto')]
    protected ?string $id = null;
    #[MongoDB\Field(type: 'date')]
    protected ?\DateTime $fechaCreacion = null;
    #[MongoDB\Field(type: 'float')]
    private float $tasa = 0.0;
    #[MongoDB\Field(type: 'float')]
    private float $usd_vef = 0.0;
    #[MongoDB\Field(type: 'float')]
    private float $usd_cop = 0.0;

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

    public function getTasa(): float
    {
        return $this->tasa;
    }

    public function setTasa(float $tasa): void
    {
        $this->tasa = $tasa;
    }

    public function getUsdVef(): float
    {
        return $this->usd_vef;
    }

    public function setUsdVef(float $usd_vef): void
    {
        $this->usd_vef = $usd_vef;
    }

    public function getUsdCop(): float
    {
        return $this->usd_cop;
    }

    public function setUsdCop(float $usd_cop): void
    {
        $this->usd_cop = $usd_cop;
    }

    public function calcularTasa(): void
    {
        $this->tasa = $this->usd_vef > 0 ? $this->usd_cop / $this->usd_vef : 0.0;
    }
}
